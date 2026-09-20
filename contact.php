<?php
require_once 'config.php';
require_once 'mailer.php';

$successMessage = '';
$errorMessage = '';

$formData = [
    'name' => '',
    'email' => '',
    'subject' => 'General inquiry',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['subject'] = trim($_POST['subject'] ?? 'General inquiry');
    $formData['message'] = trim($_POST['message'] ?? '');

    if (!empty($formData['name']) && !empty($formData['email']) && !empty($formData['message'])) {
        if (filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            // Keep values within sane sizes.
            $formData['name']    = mb_substr($formData['name'], 0, 100);
            $formData['subject'] = mb_substr($formData['subject'], 0, 150);
            $formData['message'] = mb_substr($formData['message'], 0, 5000);

            if (time() - ($_SESSION['contact_last_sent'] ?? 0) < 30) {
                // Basic spam brake — the mail goes out through our own SMTP account.
                $errorMessage = "Please wait a few seconds before sending another message.";
            } elseif (CONTACT_EMAIL === '') {
                error_log('contact.php: CONTACT_EMAIL / SMTP is not configured in .env');
                $errorMessage = "Sorry, we can't accept messages right now. Please try again later.";
            } else {
                $h = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

                $html = '<p><strong>From:</strong> ' . $h($formData['name']) . ' &lt;' . $h($formData['email']) . '&gt;</p>'
                      . '<p><strong>Subject:</strong> ' . $h($formData['subject']) . '</p><hr>'
                      . '<p>' . nl2br($h($formData['message'])) . '</p>';
                $text = "From: {$formData['name']} <{$formData['email']}>\nSubject: {$formData['subject']}\n\n{$formData['message']}";

                if (sendMail(CONTACT_EMAIL, 'LocalKart contact: ' . $formData['subject'], $html, $text, $formData['email'])) {
                    $_SESSION['contact_last_sent'] = time();
                    $successMessage = "Thank you, {$formData['name']}! Your message has been sent. We'll get back to you soon.";
                    $formData = ['name' => '', 'email' => '', 'subject' => 'General inquiry', 'message' => ''];
                } else {
                    $errorMessage = "Sorry, we couldn't send your message right now. Please try again in a few minutes.";
                }
            }
        } else {
            $errorMessage = "Please enter a valid email address.";
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - LocalKart</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #FBF8F1;
            margin: 0;
            color: #333;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 40px auto;
        }

        .contact-header h1 {
            color: #2F5233;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-family: 'Fraunces', serif;
        }

        .contact-header p {
            color: #565C4E;
            font-size: 1.1rem;
        }

        .helpdesk-note {
            max-width: 600px;
            margin: 0 auto 30px auto;
            background: #F1E9DA;
            border: 1px solid #E3DBC8;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 0.92rem;
            color: #565C4E;
            text-align: center;
        }

        .helpdesk-note a {
            color: #2F5233;
            font-weight: 600;
            text-decoration: underline;
        }

        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
            background: #FFFFFF;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        @media (max-width: 768px) {
            .contact-wrapper {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        .contact-info h3 {
            color: #2F5233;
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .info-card {
            margin-bottom: 25px;
        }

        .info-card strong {
            display: block;
            color: #20391F;
            font-size: 1.05rem;
            margin-bottom: 5px;
        }

        .info-card p {
            color: #565C4E;
            margin: 0;
        }

        .contact-form .form-group {
            margin-bottom: 20px;
        }

        .contact-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #2F5233;
        }

        .contact-form input,
        .contact-form select,
        .contact-form textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
            background: #fff;
        }

        .contact-form input:focus,
        .contact-form select:focus,
        .contact-form textarea:focus {
            border-color: #2F5233;
        }

        .contact-form textarea {
            resize: vertical;
            height: 140px;
        }

        .btn-submit {
            background: #2F5233;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .btn-submit:hover {
            background: #20391F;
            box-shadow: 0 4px 12px rgba(47, 82, 51, 0.25);
        }

        .alert-success {
            background: #e6f4ea;
            color: #137333;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ceead6;
        }

        .alert-error {
            background: #fce8e6;
            color: #c5221f;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fad2cf;
        }
    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <div class="container">
        <div class="contact-header">
            <h1>Get in Touch</h1>
            <p>Wondering how LocalKart works, or running into a technical hiccup — broken images, trouble logging in, or an issue with your customer or vendor account? We're here to help.</p>
        </div>

        <div class="helpdesk-note">
            Already a customer with a question about an order or your account? 
            <a href="helpdesk.php">Visit the Help Desk</a> instead — it's tracked and tied to your account. 
            Use the form below for general questions that aren't order-specific.
        </div>

        <div class="contact-wrapper">
            <div class="contact-info">
                <h3>Contact Information</h3>
                <div class="info-card">
                    <strong>📍 Our Office</strong>
                    <p>Ghatkopar East,Mumbai</p>
                </div>
                <div class="info-card">
                    <strong>📧 Email Us</strong>
                    <p>support@localkart.com</p>
                </div>
                <div class="info-card">
                    <strong>📞 Phone Support</strong>
                    <p>+91 8104625140</p>
                </div>
                <div class="info-card">
                    <strong>⏰ Support Hours</strong>
                    <p>Monday – Saturday: 8:00 AM – 8:00 PM</p>
                </div>
            </div>

            <div class="contact-form">
                <?php if (!empty($successMessage)): ?>
                    <div class="alert-success"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-error"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <?php csrfField(); ?>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Shubham Giri" 
                               value="<?= htmlspecialchars($formData['name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="shubham@example.com" 
                               value="<?= htmlspecialchars($formData['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject">
                            <?php 
                            $subjects = [
                                'General inquiry', 
                                'How the website works', 
                                'Login or authentication issue', 
                                'Customer account issue', 
                                'Vendor account or storefront issue', 
                                'Broken images or display bug', 
                                'Feedback or suggestions', 
                                'Other'
                            ];
                            foreach ($subjects as $option): 
                            ?>
                                <option value="<?= htmlspecialchars($option) ?>" <?= $formData['subject'] === $option ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required 
                                  placeholder="Write your message here..."><?= htmlspecialchars($formData['message']) ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

</body>
</html>