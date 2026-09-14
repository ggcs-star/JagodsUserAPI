<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>One Time Password</title>

    <style>
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }

            .email-content {
                padding: 30px 20px !important;
            }

            .otp-box {
                font-size: 28px !important;
                letter-spacing: 6px !important;
            }

            .logo {
                max-width: 150px !important;
            }
        }
    </style>
</head>

<body style="
    margin: 0;
    padding: 0;
    width: 100%;
    background-color: #f4f6f8;
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2937;
">

    <table
        width="100%"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="background-color: #f4f6f8; padding: 45px 15px;"
    >
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table
                    width="600"
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    class="email-container"
                    style="
                        width: 600px;
                        max-width: 600px;
                        background-color: #ffffff;
                        border-radius: 14px;
                        overflow: hidden;
                        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
                    "
                >

                    <!-- Header -->
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 32px 30px;
                                border-bottom: 1px solid #eeeeee;
                            "
                        >
                            <img
                                class="logo"
                                src="{{ themeSetting('site_logo')
                                    ? themeSetting('site_logo')->logo
                                    : rtrim(env('MEDIA_URL'), '/') . '/images/seeder/settings/logo.png' }}"
                                alt="Jagods"
                                style="
                                    display: block;
                                    max-width: 180px;
                                    width: auto;
                                    height: auto;
                                    border: 0;
                                "
                            >
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td
                            class="email-content"
                            style="padding: 42px 45px 35px;"
                        >

                            <p style="
                                margin: 0 0 10px;
                                font-size: 15px;
                                line-height: 24px;
                                color: #6b7280;
                            ">
                                Hello {{ $name }},
                            </p>

                            <h1 style="
                                margin: 0 0 14px;
                                font-size: 28px;
                                line-height: 36px;
                                font-weight: 700;
                                color: #111827;
                            ">
                                Verify your account
                            </h1>

                            <p style="
                                margin: 0 0 30px;
                                font-size: 15px;
                                line-height: 25px;
                                color: #6b7280;
                            ">
                                Use the One Time Password below to complete your
                                verification. This code is valid for
                                <strong style="color: #111827;">5 minutes</strong>.
                            </p>

                            <!-- OTP Box -->
                            <table
                                width="100%"
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                style="margin: 0 0 30px;"
                            >
                                <tr>
                                    <td
                                        align="center"
                                        style="
                                            background-color: #f8fafc;
                                            border: 1px solid #e5e7eb;
                                            border-radius: 10px;
                                            padding: 24px 15px;
                                        "
                                    >
                                        <p style="
                                            margin: 0 0 10px;
                                            font-size: 12px;
                                            line-height: 18px;
                                            font-weight: 600;
                                            letter-spacing: 1.5px;
                                            text-transform: uppercase;
                                            color: #9ca3af;
                                        ">
                                            Verification Code
                                        </p>

                                        <div
                                            class="otp-box"
                                            style="
                                                font-size: 34px;
                                                line-height: 42px;
                                                font-weight: 700;
                                                letter-spacing: 9px;
                                                color: #111827;
                                            "
                                        >
                                            {{ $otp }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Note -->
                            <table
                                width="100%"
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                style="
                                    background-color: #fffbeb;
                                    border: 1px solid #fde68a;
                                    border-radius: 8px;
                                    margin-bottom: 25px;
                                "
                            >
                                <tr>
                                    <td style="padding: 15px 17px;">

                                        <p style="
                                            margin: 0;
                                            font-size: 13px;
                                            line-height: 21px;
                                            color: #92400e;
                                        ">
                                            <strong>Security notice:</strong>
                                            Never share this OTP with anyone.
                                            Our team will never ask you for your
                                            verification code.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                            <p style="
                                margin: 0;
                                font-size: 14px;
                                line-height: 22px;
                                color: #6b7280;
                            ">
                                If you did not request this verification code,
                                you can safely ignore this email.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 22px 30px;
                                background-color: #f9fafb;
                                border-top: 1px solid #eeeeee;
                            "
                        >
                            <p style="
                                margin: 0 0 6px;
                                font-size: 12px;
                                line-height: 18px;
                                color: #9ca3af;
                            ">
                                This is an automated email. Please do not reply.
                            </p>

                            <p style="
                                margin: 0;
                                font-size: 12px;
                                line-height: 18px;
                                color: #9ca3af;
                            ">
                                &copy; {{ date('Y') }} Jagods. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>