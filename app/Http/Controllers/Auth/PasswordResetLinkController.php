<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Corbital\LaravelEmails\Facades\Email;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $reCaptchSettings = get_batch_settings([
            're-captcha.isReCaptchaEnable',
            're-captcha.secret_key',
        ]);

        if ($reCaptchSettings['re-captcha.isReCaptchaEnable']) {

            $recaptchaResponse = $request->input('g-recaptcha-response');
            $secretKey = $reCaptchSettings['re-captcha.secret_key'];

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $recaptchaResponse,
                'remoteip' => $request->ip(),
            ]);

            $recaptchaResult = $response->json();

            if (! $recaptchaResult['success'] || $recaptchaResult['score'] < 0.5) {
                throw ValidationException::withMessages([
                    'g-recaptcha-response' => [t('email_recaptcha_failed')],
                ]);
            }
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        $emailConfigured = true;

        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) use (&$emailConfigured) {

                try {

                    if ($user->user_type == 'admin') {
                        $content = render_email_template('password-reset', ['userId' => $user->id, 'reset_url' => $token]);
                        $subject = get_email_subject('password-reset', ['userId' => $user->id, 'reset_url' => $token]);
                        $template_active = true;
                    } else {
                        $content = render_email_template('tenant-password-reset', ['userId' => $user->id, 'reset_url' => $token, 'tenantId' => $user->tenant_id], 'tenant_email_templates');
                        $subject = get_email_subject('tenant-password-reset', ['userId' => $user->id, 'reset_url' => $token, 'tenantId' => $user->tenant_id], 'tenant_email_templates');
                        $template_active = can_send_email('tenant-password-reset', 'tenant_email_templates', $user->tenant_id);
                    }

                    if (is_smtp_valid() && $template_active) {
                        $status = Email::to($user->email)
                            ->subject($subject)
                            ->content($content)
                            ->send();

                        $status = ($status == true) ? Password::RESET_LINK_SENT : false;

                        return $status;
                    } else {
                        $emailConfigured = false;

                        return false;
                    }

                } catch (\Exception $e) {
                    $status = false;

                    return false;
                }
            }
        );

        if (! $emailConfigured) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => t('email_config_is_required')]);
        }

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
