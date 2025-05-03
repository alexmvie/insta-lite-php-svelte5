<?php
class Email {
    public function sendVerificationEmail($email, $verification_token) {
        $message = "
        Welcome to Insta-Lite!\n\n
        Thank you for registering. Please verify your email address by clicking the link below:\n\n
        http://localhost:8000/verify.php?token={$verification_token}\n\n
        If you didn't register for Insta-Lite, please ignore this email.\n\n
        Best regards,\n
        The Insta-Lite Team
        ";

        // For development, just log the email instead of sending it
        error_log("Verification email would be sent to: $email\nToken: $verification_token\nMessage: $message");
        return true;
    }
}
