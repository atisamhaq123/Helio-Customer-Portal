"""HTTP integration checks using only Python's standard library.
Run against a server configured with a disposable HELIO_DB_PATH.
"""
import datetime
import http.cookiejar
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://localhost:8080/'

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(browser, path):
    try:
        response = browser.open(BASE + path)
        return response.status, response.read().decode('utf-8'), response.geturl()
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode('utf-8'), error.geturl()

def post(browser, path, fields):
    request = urllib.request.Request(BASE + path, urllib.parse.urlencode(fields).encode())
    try:
        response = browser.open(request)
        return response.status, response.read().decode('utf-8'), response.geturl()
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode('utf-8'), error.geturl()

def token(body):
    return re.search(r'name="csrf" value="([a-f0-9]+)"', body).group(1)

def login(browser, email, password):
    _, body, _ = get(browser, 'login.php')
    return post(browser, 'login.php', {'csrf':token(body), 'email':email, 'password':password})

customer, admin, anonymous = client(), client(), client()
assert 'login.php' in get(anonymous, 'index.php')[2], 'Unauthenticated dashboard must redirect'
assert 'login.php' in get(anonymous, 'download.php?id=1')[2], 'Unauthenticated download must redirect'
assert post(anonymous, 'login.php', {'csrf':'bad','demo':'1'})[0] == 419
assert 'incorrect' in login(customer, 'alex@helio.demo', 'wrong')[1]
code, body, _ = login(customer, 'alex@helio.demo', 'HelioDemo!2026')
assert code == 200 and 'Energy overview' in body
for page in ['dashboard','energy','project','billing','documents','appointments','support','settings']:
    code, body, _ = get(customer, 'index.php?page=' + page)
    assert code == 200 and 'Fatal error' not in body and 'Warning:' not in body, page
assert 'Admin workspace' not in get(customer, 'index.php?page=admin')[1]
_, body, _ = get(customer, 'index.php?page=support')
csrf = token(body)
assert post(customer, 'actions.php', {'csrf':'bad','action':'ticket'})[0] == 419
assert post(customer, 'actions.php', {'csrf':csrf,'action':'admin_ticket','id':1,'status':'Resolved','reply':'No'})[0] == 403
code, body, _ = post(customer, 'actions.php', {'csrf':csrf,'action':'ticket','subject':'<script>test</script>','message':'Test request: monitoring needs attention.','category':'Monitoring'})
assert code == 200 and '&lt;script&gt;test&lt;/script&gt;' in body and '<script>test</script>' not in body
ticket_id = int(re.search(r'HEL-(\d+) · Monitoring', body).group(1))
code, body, _ = post(customer, 'actions.php', {'csrf':csrf,'action':'appointment','date':'2020-01-01','time':'10:00','type':'System health check'})
assert 'Choose a valid appointment' in body
# Server uses PKT. Choose a date safely in the future across time zones.
for offset in range(20, 89):
    visit_date = (datetime.date.today() + datetime.timedelta(days=offset)).isoformat()
    fields = {'csrf':csrf,'action':'appointment','date':visit_date,'time':'14:00','type':'Maintenance visit','notes':'Smoke test appointment'}
    result = post(customer, 'actions.php', fields)[1]
    if 'Appointment requested.' in result:
        break
    assert 'already have a visit' in result
else:
    raise AssertionError('No free appointment slot in disposable test database')
assert 'already have a visit' in post(customer, 'actions.php', fields)[1]
assert get(customer, 'download.php?id=1')[0] == 200
assert 'DEMO DOCUMENT' in get(customer, 'download.php?id=1')[1]
assert get(customer, 'download.php?id=99999')[0] == 404
assert 'Generation kWh' in get(customer, 'download.php?type=report&id=1')[1]
code, body, _ = login(admin, 'admin@helio.demo', 'HelioAdmin!2026')
assert code == 200 and 'Admin workspace' in body
_, body, _ = get(admin, 'index.php?page=admin')
admin_csrf = token(body)
assert get(admin, 'download.php?id=1')[0] == 404, 'Another account must not download customer document'
assert get(admin, 'download.php?type=invoice&id=1')[0] == 404
assert get(admin, 'download.php?type=report&id=1')[0] == 404
assert 'Changes saved.' in post(admin, 'actions.php', {'csrf':admin_csrf,'action':'admin_ticket','id':ticket_id,'status':'Resolved','reply':'Your system has been checked.'})[1]
_, body, _ = get(customer, 'index.php?page=support')
assert 'Your system has been checked.' in body
assert post(customer, 'actions.php', {'csrf':csrf,'action':'logout'})[2].endswith('login.php')
assert 'login.php' in get(customer, 'index.php')[2]
print('PASS: login, 8 customer screens, CSRF, role checks, escaped ticket content, appointments, duplicate validation, downloads, account isolation, admin replies, and logout.')
