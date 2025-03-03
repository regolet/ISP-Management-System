<?php
namespace App\Helpers;

class NumberToWords {
    private static $dictionary = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];

    private static function convertLessThanOneThousand($number) {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return self::$dictionary[$number];
        }

        if ($number < 100) {
            $tens = floor($number / 10) * 10;
            $ones = $number % 10;
            return self::$dictionary[$tens] . ($ones ? '-' . self::$dictionary[$ones] : '');
        }

        $hundreds = floor($number / 100);
        $remainder = $number % 100;
        return self::$dictionary[$hundreds] . ' Hundred' . 
               ($remainder ? ' and ' . self::convertLessThanOneThousand($remainder) : '');
    }

    public static function convert($number) {
        $number = number_format($number, 2, '.', '');
        list($dollars, $cents) = explode('.', $number);
        $dollars = (int)$dollars;

        if ($dollars == 0) {
            $result = 'Zero';
        } else {
            $billions = floor($dollars / 1000000000);
            $millions = floor(($dollars % 1000000000) / 1000000);
            $thousands = floor(($dollars % 1000000) / 1000);
            $remainder = $dollars % 1000;

            $result = '';
            if ($billions) {
                $result .= self::convertLessThanOneThousand($billions) . ' Billion ';
            }
            if ($millions) {
                $result .= self::convertLessThanOneThousand($millions) . ' Million ';
            }
            if ($thousands) {
                $result .= self::convertLessThanOneThousand($thousands) . ' Thousand ';
            }
            if ($remainder) {
                $result .= self::convertLessThanOneThousand($remainder);
            }
        }

        if ($cents > 0) {
            $result .= ' and ' . $cents . '/100';
        }

        return trim($result);
    }
}
