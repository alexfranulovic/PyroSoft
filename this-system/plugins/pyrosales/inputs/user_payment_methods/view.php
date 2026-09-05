<?php

if (!function_exists('view_payment_methods_field'))
{
    function view_user_payment_methods_field(array $params = [])
    {
        extract($params);

        return render_card_brand_html($field_value);
    }
}
