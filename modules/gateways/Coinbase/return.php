<?php
/**
 * Coinbase Payment Return Page
 *
 * Landing page shown after the user completes (or cancels) payment on Coinbase.
 * Displays a processing/failure message instead of redirecting straight to the
 * invoice page, which would show "Unpaid" before the async webhook arrives.
 */

require_once __DIR__ . '/../../../init.php';

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$invoiceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$success = isset($_GET['paymentsuccess']);
$failed = isset($_GET['paymentfailed']);

$systemUrl = \Illuminate\Database\Capsule\Manager::table('tblconfiguration')
    ->where('setting', 'SystemURL')
    ->value('value');

$invoiceUrl = $systemUrl . 'viewinvoice.php?id=' . $invoiceId;
$homeUrl = $systemUrl . 'clientarea.php';

if ($success) {
    $title = 'Payment Submitted';
    $heading = 'Your payment has been submitted';
    $message = 'Your cryptocurrency payment is being confirmed on the network. '
        . 'This usually takes a few minutes. You will receive an email confirmation once the payment is complete.';
    $iconColor = '#0052FF';
    $iconPath = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
} elseif ($failed) {
    $title = 'Payment Not Completed';
    $heading = 'Your payment was not completed';
    $message = 'The payment was cancelled or could not be processed. No funds have been deducted. '
        . 'You can try again from your invoice page.';
    $iconColor = '#D97706';
    $iconPath = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z';
} else {
    header('Location: ' . $homeUrl);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #F7F8FA;
            color: #1A1A2E;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .icon { margin-bottom: 1.25rem; }
        .icon svg { width: 56px; height: 56px; }
        h1 {
            font-size: 1.375rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1.75rem;
            font-size: 0.95rem;
        }
        .btn {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary {
            background: #0052FF;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="<?php echo $iconColor; ?>" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="<?php echo $iconPath; ?>" />
            </svg>
        </div>
        <h1><?php echo htmlspecialchars($heading); ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="<?php echo htmlspecialchars($invoiceUrl); ?>" class="btn btn-primary">View Invoice</a>
    </div>
</body>
</html>
