<?php
if(!isset($seg)) exit;


/**
 * Formats a numeric value as US Dollar currency.
 *
 * @param float $value The numeric value to format.
 * @return string The formatted value in US Dollar currency.
 */
function USD($value)
{
    return "$" . number_format($value, 2, ".", "," );
}


/**
 * Converts a localized numeric string into a normalized decimal format.
 *
 * This function removes thousand separators (dots and spaces),
 * replaces the decimal comma with a dot, casts the result to float,
 * and formats it with 2 decimal places using a dot as the separator.
 *
 * @param mixed $value The input value, usually a string with localized formatting (e.g., "1.234,56").
 * @return string A string representation of the number in decimal format with two digits after the decimal point (e.g., "1234.56").
 */
function DECIMAL($value)
{
    $value = str_replace(['.', ' '], '', $value);
    $value = str_replace(',', '.', $value);

    return number_format((float) $value, 2, '.', '');
}


/**
 * Formats a numeric value as Brazilian Real currency.
 *
 * @param float $value The numeric value to format.
 * @return string The formatted value in Brazilian Real currency.
 */
function BRL($value)
{
    return "R$" . number_format($value, 2, ",", "." );
}


/**
 * Format a rating with a score and the number of ratings
 *
 * @param float $score The score of the rating
 * @param int $rating The number of ratings
 * @return string The formatted rating with score and number of ratings
 */
function rating_format($score, $rating)
{
    if ($score == 0) {
        $score = 'N/A';
    }else{
        $score = number_format($score, 1);
    }

    return "$score / $rating avaliações";
}


/**
 * Format shipping information.
 *
 * @param int $type The type of shipping.
 * @param float $price The price of shipping.
 * @param int|null $deadline The deadline for delivery (optional).
 * @return string The formatted shipping information.
 */
function shipping_format($type, $price, $deadline = null)
{
    $return = "";
    $return.= BRL($price);

    if ($type == 1)     $return .= ' / PAC';
    elseif ($type == 2) $return .= ' / SEDEX';
    elseif ($type == 3) $return .= ' / Sem frete';

    if ($deadline === 0 && $deadline !== null) {
        $return .= ' / A combinar.';
    } elseif ($deadline !== 0 && $deadline !== null) {
        $return .= ' / prazo de: ' . $deadline . ' dias.';
    }

    return $return;
}


/**
 * Validates a Brazilian CPF (Cadastro de Pessoas Físicas) number.
 *
 * @param string $cpf The CPF number to validate.
 * @return bool Returns true if the CPF is valid, false otherwise.
 */
function validate_cpf($cpf = null, bool $is_form = false)
{
    $is_valid = true;

    if (empty($cpf)) return;

    $cpf = clean_number($cpf);

    if (strlen($cpf) != 11)               $is_valid = false;
    if (preg_match('/(\d)\1{10}/', $cpf)) $is_valid = false;

    if ($is_valid)
    {
        for ($t = 9; $t < 11; $t++)
        {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) $is_valid = false;
        }
    }

    if ($is_form)
    {
        if (!$is_valid)
        {
            return [
                'error' => true,
                'msg'   => "Esse CPF é inválido."
            ];
        }

        return $cpf;
    }

    return $is_valid;
}


/**
 * Formats a CNPJ (Cadastro Nacional da Pessoa Jurídica) or CPF (Cadastro de Pessoas Físicas) number.
 *
 * @param string $value The CNPJ or CPF number to format.
 * @return string The formatted CNPJ or CPF number.
 */
function format_cnpj_cpf($value)
{
    $cnpj_cpf = preg_replace("/\D/", '', $value);

    if (strlen($cnpj_cpf) === 11) return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);

    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
}


/**
 * Formats a phone number.
 *
 * @param string $value The phone number to format.
 * @return string The formatted phone number.
 */
function format_phone($value)
{
    if (strlen($value) === 11)     return preg_replace("/(\d{2})(\d{5})(\d{4})/", "(\$1) \$2-\$3", $value);
    elseif (strlen($value) === 10) return preg_replace("/(\d{2})(\d{4})(\d{4})/", "(\$1) \$2-\$3", $value);
}


/**
 * Formats a ZIP code (CEP).
 *
 * @param string $value The ZIP code to format.
 * @return string The formatted ZIP code.
 */
function format_zipcode($value = '')
{
    return !empty($value) ? preg_replace("/(\d{2})(\d{3})(\d{3})/", "\$1.\$2-\$3", $value) : '';
}


/**
 * Formats a date and time.
 *
 * @param string $value The date and time value to format.
 * @return string The formatted date and time.
 */
function format_datetime($value)
{
    return date('d/m/Y - H:i', strtotime($value));
}


/**
 * Formats a price for freight.
 *
 * @param string $value The price value to format.
 * @return string The formatted price.
 */
function format_shipping_price($value)
{
    $price = preg_replace("/\D/", '', $value);
    if (strlen($price) === 4)     return preg_replace("/(\d{2})(\d{2})/", "\$1.\$2", $price);
    elseif (strlen($price) === 5) return preg_replace("/(\d{3})(\d{2})/", "\$1.\$2", $price);
    elseif (strlen($price) === 3) return preg_replace("/(\d{1})(\d{2})/", "\$1.\$2", $price);
}


/**
 * Formats a full address from JSON or array input.
 *
 * Accepts:
 * - JSON string: {"street":"Av. Brasil","number":"100","city":"Rio"}
 * - Array: ['street'=>'...', 'number'=>'...']
 *
 * @param mixed $value
 * @return string
 */
function format_address($value)
{
    // Convert JSON to array if necessary
    $addr = is_json($value) ? json_decode($value, true) : (array) $value;

    // Normalize values and avoid undefined indexes
    $street     = trim($addr['street']     ?? '');
    $number     = trim($addr['number']     ?? '');
    $complement = trim($addr['complement'] ?? '');
    $district   = trim($addr['district']   ?? '');
    $city       = trim($addr['city']       ?? '');
    $state      = trim($addr['state']      ?? '');
    $zipcode    = trim($addr['zipcode']    ?? '');
    $country    = trim($addr['country']    ?? '');

    // Build main address parts, skipping empty ones
    $parts = [];

    if ($street !== '')       $parts[] = $street;
    if ($number !== '')       $parts[] = $number;
    if ($complement !== '')   $parts[] = $complement;
    if ($district !== '')     $parts[] = $district;
    if ($city !== '')         $parts[] = $city;
    if ($state !== '')        $parts[] = $state;
    if ($country !== '')      $parts[] = $country;

    // Join with spaces and commas in a natural way
    $formatted = implode(', ', array_filter($parts));

    // Append CEP if present
    if ($zipcode !== '') {
        $formatted .= ' — CEP: ' . format_zipcode($zipcode);
    }

    return $formatted;
}


/**
 * Retrieves a list of states with their names and initials.
 *
 * @param bool $for_selects Optional. Determines the output format. If set to true, returns a formatted string for selects. Default is false.
 * @return array|string The list of states as an array of objects or a formatted string for selects.
 */
function states_address(bool $for_selects = false)
{
    $states = [
        [ 'name' => 'Acre', 'initials' => 'AC'],
        [ 'name' => 'Alagoas', 'initials' => 'AL'],
        [ 'name' => 'Amazonas', 'initials' => 'AM'],
        [ 'name' => 'Amapá', 'initials' => 'AP'],
        [ 'name' => 'Bahia', 'initials' => 'BA'],
        [ 'name' => 'Ceará', 'initials' => 'CE'],
        [ 'name' => 'Distrito Federal', 'initials' => 'DF'],
        [ 'name' => 'Espírito Santo', 'initials' => 'ES'],
        [ 'name' => 'Goiás', 'initials' => 'GO'],
        [ 'name' => 'Maranhão', 'initials' => 'MA'],
        [ 'name' => 'Minas Gerais', 'initials' => 'MG'],
        [ 'name' => 'Mato Grosso do Sul', 'initials' => 'MS'],
        [ 'name' => 'Mato Grosso', 'initials' => 'MT'],
        [ 'name' => 'Pará', 'initials' => 'PA'],
        [ 'name' => 'Paraíba', 'initials' => 'PE'],
        [ 'name' => 'Pernambuco', 'initials' => 'PE'],
        [ 'name' => 'Piauí', 'initials' => 'PI'],
        [ 'name' => 'Paraná', 'initials' => 'PR'],
        [ 'name' => 'Rio de Janeiro', 'initials' => 'RJ'],
        [ 'name' => 'Rio Grande do Norte', 'initials' => 'RN'],
        [ 'name' => 'Rondônia', 'initials' => 'RO'],
        [ 'name' => 'Roraima', 'initials' => 'RR'],
        [ 'name' => 'Rio Grande do Sul', 'initials' => 'RS'],
        [ 'name' => 'Santa Catarina', 'initials' => 'SC'],
        [ 'name' => 'Sergipe', 'initials' => 'SE'],
        [ 'name' => 'São Paulo', 'initials' => 'SP'],
        [ 'name' => 'Tocantins', 'initials' => 'TO'],
    ];

    $res = $states;

    if ($for_selects == true)
    {
        $res = [];
        foreach($states as $state)
        {
            $res[] = [
                'value' => $state['initials'],
                'display' => $state['name'],
            ];
        }
    }

    return $res;
}
