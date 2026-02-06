<?php

class InputValidator
{
    protected array $errors = [];

    /* ===============================
       VALIDASI WAJIB
    =============================== */
    public function required($value, string $field)
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field][] = "Field $field wajib diisi";
        }
        return $this;
    }

    /* ===============================
       VALIDASI ANGKA
    =============================== */
    public function numeric($value, string $field)
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "Field $field harus berupa angka";
        }
        return $this;
    }

    /* ===============================
       VALIDASI MIN
    =============================== */
    public function min($value, int $min, string $field)
    {
        if ($value !== null && is_numeric($value) && $value < $min) {
            $this->errors[$field][] = "Field $field minimal $min";
        }
        return $this;
    }

    /* ===============================
       VALIDASI MAX
    =============================== */
    public function max($value, int $max, string $field)
    {
        if ($value !== null && is_numeric($value) && $value > $max) {
            $this->errors[$field][] = "Field $field maksimal $max";
        }
        return $this;
    }

    /* ===============================
       VALIDASI STRING
    =============================== */
    public function string($value, string $field)
    {
        if ($value !== null && !is_string($value)) {
            $this->errors[$field][] = "Field $field harus berupa teks";
        }
        return $this;
    }

    /* ===============================
       VALIDASI EMAIL
    =============================== */
    public function email($value, string $field)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Format email $field tidak valid";
        }
        return $this;
    }

    /* ===============================
       VALIDASI PANJANG KARAKTER
    =============================== */
    public function length($value, int $min, int $max, string $field)
    {
        if ($value !== null) {
            $len = strlen((string)$value);
            if ($len < $min || $len > $max) {
                $this->errors[$field][] =
                    "Field $field harus antara $min sampai $max karakter";
            }
        }
        return $this;
    }

    /* ===============================
       VALIDASI DATA ARRAY (FIX BARIS 186)
    =============================== */
    public function validate(array $rules, array $data)
    {
        foreach ($rules as $field => $ruleSet) {

            // ✅ FIX UTAMA (PHP 8 SAFE)
            $value = $data[$field] ?? null;

            foreach ($ruleSet as $rule) {

                if (is_array($rule)) {
                    $method = $rule[0];
                    $params = array_slice($rule, 1);
                    array_unshift($params, $value, $field);
                } else {
                    $method = $rule;
                    $params = [$value, $field];
                }

                if (method_exists($this, $method)) {
                    call_user_func_array([$this, $method], $params);
                }
            }
        }
        return $this;
    }

    /* ===============================
       CEK ERROR
    =============================== */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    /* ===============================
       AMBIL ERROR
    =============================== */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}