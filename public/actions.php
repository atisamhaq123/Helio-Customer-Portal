<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$account = user();
if (!$account) { redirect('login.php'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('POST required'); }
check_csrf();
$action = (string)($_POST['action'] ?? '');
if ($action === 'logout') { $_SESSION = []; session_destroy(); redirect('login.php'); }
$page = 'dashboard';
try {
    if ($action === 'ticket') {
        $page = 'support'; $subject = trim((string)($_POST['subject'] ?? '')); $message = trim((string)($_POST['message'] ?? '')); $category = (string)($_POST['category'] ?? '');
        if (strlen($subject) < 5 || strlen($subject) > 150 || strlen($message) < 10 || strlen($message) > 4000 || !in_array($category,['Monitoring','Equipment','Billing','General'],true)) { throw new RuntimeException('Enter a subject of 5–150 characters and a message of 10–4,000 characters.'); }
        run('INSERT INTO tickets (user_id,subject,message,category,created_at) VALUES (?,?,?,?,?)',[$account['id'],$subject,$message,$category,date('Y-m-d H:i:s')]); audit('Created a support ticket'); flash('Your request is saved. Our team will review it.');
    } elseif ($action === 'appointment') {
        $page = 'appointments'; $date = (string)($_POST['date'] ?? ''); $time = (string)($_POST['time'] ?? ''); $type = (string)($_POST['type'] ?? ''); $notes = trim((string)($_POST['notes'] ?? ''));
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date <= date('Y-m-d') || $date > date('Y-m-d',strtotime('+90 days')) || !in_array($time,['09:00','10:00','11:00','14:00','15:00'],true) || !in_array($type,['System health check','Maintenance visit','Site survey'],true) || strlen($notes)>2000) { throw new RuntimeException('Choose a valid appointment in the next 90 days.'); }
        if (query('SELECT id FROM appointments WHERE user_id=? AND date=? AND time=? AND status IN ("Requested","Confirmed")',[$account['id'],$date,$time])) { throw new RuntimeException('You already have a visit requested for that time.'); }
        run('INSERT INTO appointments (user_id,type,date,time,notes) VALUES (?,?,?,?,?)',[$account['id'],$type,$date,$time,$notes]); audit('Requested an appointment'); flash('Appointment requested. Your time is pending confirmation by the team.');
    } elseif ($action === 'profile') {
        $page = 'settings'; $name = trim((string)($_POST['name'] ?? '')); $email = trim((string)($_POST['email'] ?? ''));
        if (strlen($name)<2 || strlen($name)>80 || strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Enter a valid name and email address.'); }
        if (query('SELECT id FROM users WHERE email=? AND id<>?',[$email,$account['id']])) { throw new RuntimeException('That email address is already in use.'); }
        run('UPDATE users SET name=?,email=? WHERE id=?',[$name,$email,$account['id']]); audit('Updated profile'); flash('Your profile has been updated.');
    } elseif ($action === 'password') {
        $page = 'settings'; $stored = query('SELECT password FROM users WHERE id=?',[$account['id']])[0]['password']; $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''),$stored)) { throw new RuntimeException('Current password is incorrect.'); }
        if (strlen($new)<12 || strlen($new)>72 || $new !== (string)($_POST['confirm_password'] ?? '')) { throw new RuntimeException('Use a matching password of 12–72 characters.'); }
        run('UPDATE users SET password=? WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$account['id']]); session_regenerate_id(true); audit('Changed password'); flash('Password updated.');
    } elseif ($action === 'admin_ticket' || $action === 'admin_appointment' || $action === 'admin_stage') {
        $page = 'admin'; if ($account['role'] !== 'admin') { http_response_code(403); exit('Access denied'); }
        $id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); if (!$id) { throw new RuntimeException('Invalid record.'); }
        if ($action === 'admin_ticket') {
            $status = (string)($_POST['status'] ?? ''); $reply = trim((string)($_POST['reply'] ?? '')); if (!in_array($status,['Open','In progress','Resolved'],true) || strlen($reply)>4000) { throw new RuntimeException('Invalid ticket update.'); }
            if (!query('SELECT id FROM tickets WHERE id=?',[$id])) { throw new RuntimeException('Ticket not found.'); }
            run('UPDATE tickets SET status=?,reply=? WHERE id=?',[$status,$reply,$id]);
        } elseif ($action === 'admin_appointment') {
            $status = (string)($_POST['status'] ?? ''); if (!in_array($status,['Requested','Confirmed','Completed','Cancelled'],true)) { throw new RuntimeException('Invalid appointment status.'); }
            if (!query('SELECT id FROM appointments WHERE id=?',[$id])) { throw new RuntimeException('Appointment not found.'); }
            run('UPDATE appointments SET status=? WHERE id=?',[$status,$id]);
        } else {
            $stage = filter_input(INPUT_POST,'stage',FILTER_VALIDATE_INT); if ($stage === false || $stage === null || $stage < 0 || $stage > 4) { throw new RuntimeException('Invalid project stage.'); }
            if (!query('SELECT id FROM properties WHERE id=?',[$id])) { throw new RuntimeException('Property not found.'); }
            run('UPDATE properties SET stage=? WHERE id=?',[$stage,$id]);
        }
        audit('Updated '.$action.' record #'.$id); flash('Changes saved.');
    } else { throw new RuntimeException('Unknown action.'); }
} catch (RuntimeException $error) { flash($error->getMessage(),'danger'); }
redirect('index.php?page='.$page);
