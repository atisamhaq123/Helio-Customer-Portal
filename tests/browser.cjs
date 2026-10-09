const { chromium } = require('../.tools/node_modules/playwright');
(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1050 }, deviceScaleFactor: 1 });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('http://localhost:8080');
  await page.screenshot({ path: '.tools/login-desktop.png', fullPage: true });
  await page.getByRole('button', { name: 'Enter customer demo' }).click();
  await page.locator('#energy-chart').waitFor();
  await page.waitForTimeout(1500);
  const bootstrapLoaded = await page.evaluate(() => !!window.bootstrap && getComputedStyle(document.querySelector('.row')).display === 'flex');
  if (!bootstrapLoaded) throw new Error('Bootstrap CSS or JS did not load');
  await page.screenshot({ path: '.tools/dashboard-desktop.png', fullPage: true });
  await page.getByRole('button', { name: 'Week', exact: true }).click();
  if (!(await page.locator('#chart-date').textContent()).includes('week')) throw new Error('Chart period did not change');
  await page.selectOption('#property', '2');
  await page.waitForURL(/property=2/);
  if (!(await page.locator('.system-strip').textContent()).includes('5.6')) throw new Error('Property switch failed');
  await page.goto('http://localhost:8080/index.php?page=documents');
  await page.locator('#document-search').fill('warranty');
  if (await page.locator('.document-item:visible').count() !== 1) throw new Error('Document search failed');
  await page.locator('#document-search').fill('no match found');
  if (!(await page.locator('#document-empty').isVisible())) throw new Error('Search empty state missing');
  for (const name of ['dashboard','energy','project','billing','documents','appointments','support','settings']) {
    await page.goto('http://localhost:8080/index.php?page=' + name);
    if (await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1)) throw new Error('Desktop overflow on ' + name);
  }
  await page.setViewportSize({ width: 390, height: 844 });
  for (const name of ['dashboard','energy','project','billing','documents','appointments','support','settings']) {
    await page.goto('http://localhost:8080/index.php?page=' + name);
    if (await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1)) throw new Error('Mobile overflow on ' + name);
  }
  await page.goto('http://localhost:8080/index.php?property=1');
  await page.screenshot({ path: '.tools/dashboard-mobile.png', fullPage: true });
  await page.getByRole('button', { name: 'Open navigation' }).click();
  if (!(await page.locator('#sidebar').evaluate(el => el.classList.contains('open')))) throw new Error('Mobile menu did not open');
  await page.keyboard.press('Escape');
  if (await page.locator('#sidebar').evaluate(el => el.classList.contains('open'))) throw new Error('Mobile menu did not close');
  if (errors.length) throw new Error(errors.join('\n'));
  console.log('PASS: Bootstrap loaded, chart controls, property switch, document search, 8 screens without overflow at desktop/mobile sizes, mobile menu, and no JavaScript exceptions.');
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
