<?php
if(!isset($seg)) exit;

/**
 * Sends an email using a specified plugin and template.
 *
 * @param array $args An associative array containing the email parameters.
 * Possible keys:
 *   - 'to': An array of recipient email addresses.
 *   - 'subject': The subject of the email.
 *   - 'body': The HTML content of the email (if no template is used).
 *   - 'template': The name of the template function to use (optional).
 *   - 'plugin': The name of the plugin function to use for sending the email (optional).
 * @return mixed The result of the email sending operation or a message if the plugin is not installed.
 */
function send_email(array $args = [], $debug = false)
{
    global $config, $info;

    $email_config = $config['email_config'];

    $template = $args['template'] ?? 'email_default_template';

    $body = function_exists($template) ? $template($args) : $args['body'];

    $payload = [
        'to' => $args['to'],
        'subject' => $args['subject'],
        'body' => $body,
    ];

    // Send the email with the plugin
    $plugin = $args['plugin'] ?? $email_config['plugin'];
    $plugin = "{$plugin}_send_email";

    if ($email_config['enable_sending'])
    {
        $res = function_exists($plugin)
            ? $plugin($payload)
            : "The selected plugin is not installed.";
    }

    if($debug) {
        echo $body;
        print_r($res);
    }

    return !$email_config['enable_sending']
        ? [
            'code' => 'success',
            'msg' => 'Sending e-mails is not enabled.'
        ]
        : $res;
}


/**
 * Generates the HTML content for the email using the default template.
 *
 * @param array $args An associative array containing the email parameters.
 * Possible keys:
 *   - 'body': The main content of the email.
 *   - 'signature': An associative array for the signature (optional).
 *     Possible keys:
 *       - 'humanized': A boolean indicating if the signature is humanized.
 *       - 'image': The URL of the profile image (if humanized).
 *       - 'name': The name to display in the signature (if humanized).
 *       - 'task': The task or role to display in the signature (if humanized).
 *       - 'contact': The contact information to display in the signature (if humanized).
 * HTML content of the email.
 */
function email_default_template(array $args = [])
{
    global $info;

    $res = "
    <!DOCTYPE html>
    <html>
    <style>
        .body {
            background-color: #f5f5f5;
            /*display: flex;*/
            font-family: 'montserrat', sans-serif;
        }
        .branding {
            background-color: {$info['brand_colors']['primary']};
            display: flex;
            padding: 2.5rem 0;
        }
        .branding img {
            margin: 0 auto;
        }
        .main {
            background-color: #fff;
            width: fit-content;
            max-width: 600px;
            margin: 0 auto;
        }
        .content {
            background-color: #fff;
            width: fit-content;
            max-width: 600px;
            margin: 0 auto;
            padding: 2.5rem 3rem;
        }
        .h1 {
            margin-top: 0;
            text-align: center;
            color: {$info['brand_colors']['primary']};
            font-size: 1.8em;
        }
        .h1 strong {
            color: {$info['brand_colors']['secondary']};
        }
        h3 {
            text-align: center;
            margin-bottom: 0;
        }
        p {
            font-size: 1rem;
            line-height: 1.5;
        }
        a {
            color: {$info['brand_colors']['primary']} !important;
        }
        .align-itens-center {
            display: flex;
            padding: 1rem 0;
        }
        .btn {
            margin: 0 auto;
            color: #141414 !important;
            text-decoration: none;
            border-radius: 1.5rem;
            font-weight: 600;
            box-shadow: none !important;
            background-color: {$info['brand_colors']['secondary']};
            border-color: {$info['brand_colors']['secondary']};
            display: inline-block;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
        }
        .signature {
            display: flex;
            padding: 2.5rem;
            background-color: {$info['brand_colors']['secondary']};
        }
        .signature.system {
            display: flex;
        }
        .signature.system h3 {
            color: #000 !important;
            font-size: 1.25rem;
            text-align: left;
            margin: 0 auto 0 0;
        }
        .signature.humanized .person {
            display: flex;
        }
        .signature.humanized .person .profile {
            margin-right: 1.5rem;
            border-radius: 10rem;
            height: 70px;
            width: 70px;
        }
        .signature.humanized .person .about h3 {
            margin: 0 0 0.2rem 0;
            font-size: 1.2rem;
        }
        .signature.humanized .person .about .task {
            background-color: {$info['brand_colors']['primary']};
            color: #fff;
            margin-top: 0;
            padding: 0.1rem 0.4rem;
            width: fit-content;
            line-height: 1.5;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .signature .logo {
            height: fit-content;
            width: auto;
            margin-left: auto;
        }
    </style>
    <body class='body'>
    <section class='main'>
        <div class='branding'>
            <img width='220' src='". pg ."/uploads/images/brand/logotype-white.png'>
        </div>
        <section class='content'>
            {$args['body']}
        </section>";

        $signature = $args['signature'] ?? false;
        if ($signature['humanized'])
        {
            $res.= "
            <footer class='signature humanized'>
                <div class='person'>
                <img class='profile' src='{$signature['image']}' height='70' width='70'>

                <section class='about'>
                    <h3>{$signature['name']}</h3>
                    <p class='task'>{$signature['task']}</p>
                    <div class='contact'>{$signature['contact']}</div>
                </section>
                </div>

                <img class='logo' src='". pg ."/uploads/images/brand/isotype-white.png'>
            </footer>";
        }

        else
        {
            $res.= "
            <footer class='signature system'>
                <h3>Abraços, <br> {$info['name']}</h3>
                <img class='logo' src='". pg ."/uploads/images/brand/isotype-white.png'>
            </footer>";
        }

    $res.= "
    </section>
    </body>
    </html>";

    return $res;
}
