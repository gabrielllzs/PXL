<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your PXL verification code</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 2px;
        }
        h1 {
            color: #1e1e1e;
            margin-top: 0;
            font-size: 22px;
        }
        .code-container {
            background: #f8f9fa;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #2563eb;
            font-family: 'Courier New', Courier, monospace;
        }
        p {
            margin: 15px 0;
            color: #555555;
        }
        .highlight {
            color: #1e1e1e;
            font-weight: 500;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 12px;
            color: #888888;
            text-align: center;
        }
        .footer p {
            margin: 8px 0;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span class="logo-text">PXL</span>
        </div>
        
        <h1>Hi {{ $username }},</h1>
        <p>Welcome to PXL! To start placing pixels and creating art with the community, please verify your email address using this code:</p>
        
        <div class="code-container">
            <div class="verification-code">{{ $code }}</div>
        </div>
        
        <p><span class="highlight">This code expires in 15 minutes.</span> Enter it in the app to complete your registration.</p>
        
        <p>If you didn't create a PXL account, you can safely ignore this email. Someone may have entered your email address by mistake.</p>
        
        <div class="footer">
            <p><strong>PXL</strong> - Place. Create. Together.</p>
            <p>This is an automated message from PXL. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
