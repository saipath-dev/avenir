<?php
namespace App\Helpers;

class StripTagsHelper {

    public function Clean($str) {

        $t = preg_replace('/<[^<|>]+?>/', '', htmlspecialchars_decode($str));
        $t = htmlentities($t, ENT_QUOTES, "UTF-8");
        $t = strip_tags($t);
        $t = trim($t);

        return $t;

    }
}
