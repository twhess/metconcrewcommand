<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $testSubject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1976d2;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $testSubject }}</h1>
    </div>
    <div class="content">
        <p>{{ $testMessage }}</p>
        <hr>
        <p><strong>This is a test email sent from MetCon Application.</strong></p>
        <p>If you received this email, your Postmark email configuration is working correctly!</p>
    </div>
    <div class="footer">
        <p>Sent via Postmark • MetCon Application</p>
    </div>
</body>
</html>
