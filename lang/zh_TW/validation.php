<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 驗證語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行包含驗證器類別使用的預設錯誤訊息。
    | 某些規則有多個版本，例如大小規則。您可以在此處調整每個訊息。
    |
    */

    'accepted' => ':attribute 必須接受。',
    'accepted_if' => '當 :other 為 :value 時，:attribute 必須接受。',
    'active_url' => ':attribute 不是有效的網址。',
    'after' => ':attribute 必須是 :date 之後的日期。',
    'after_or_equal' => ':attribute 必須是 :date 之後或相同的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、數字、破折號和底線。',
    'alpha_num' => ':attribute 只能包含字母和數字。',
    'array' => ':attribute 必須是陣列。',
    'ascii' => ':attribute 只能包含單位元組的字母數字字元和符號。',
    'before' => ':attribute 必須是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必須是 :date 之前或相同的日期。',
    'between' => [
        'array' => ':attribute 必須有 :min 到 :max 個項目。',
        'file' => ':attribute 必須在 :min 到 :max KB 之間。',
        'numeric' => ':attribute 必須在 :min 到 :max 之間。',
        'string' => ':attribute 必須在 :min 到 :max 個字元之間。',
    ],
    'boolean' => ':attribute 欄位必須為 true 或 false。',
    'can' => ':attribute 欄位包含未經授權的值。',
    'confirmed' => ':attribute 確認不符。',
    'current_password' => '密碼不正確。',
    'date' => ':attribute 不是有效的日期。',
    'date_equals' => ':attribute 必須是等於 :date 的日期。',
    'date_format' => ':attribute 不符合格式 :format。',
    'decimal' => ':attribute 必須有 :decimal 位小數。',
    'declined' => ':attribute 必須拒絕。',
    'declined_if' => '當 :other 為 :value 時，:attribute 必須拒絕。',
    'different' => ':attribute 和 :other 必須不同。',
    'digits' => ':attribute 必須是 :digits 位數字。',
    'digits_between' => ':attribute 必須在 :min 到 :max 位數字之間。',
    'dimensions' => ':attribute 圖片尺寸無效。',
    'distinct' => ':attribute 欄位有重複值。',
    'doesnt_end_with' => ':attribute 不能以下列之一結尾：:values。',
    'doesnt_start_with' => ':attribute 不能以下列之一開頭：:values。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'ends_with' => ':attribute 必須以下列之一結尾：:values。',
    'enum' => '選擇的 :attribute 無效。',
    'exists' => '選擇的 :attribute 無效。',
    'file' => ':attribute 必須是檔案。',
    'filled' => ':attribute 欄位必須有值。',
    'gt' => [
        'array' => ':attribute 必須有超過 :value 個項目。',
        'file' => ':attribute 必須大於 :value KB。',
        'numeric' => ':attribute 必須大於 :value。',
        'string' => ':attribute 必須超過 :value 個字元。',
    ],
    'gte' => [
        'array' => ':attribute 必須有 :value 個或更多項目。',
        'file' => ':attribute 必須大於或等於 :value KB。',
        'numeric' => ':attribute 必須大於或等於 :value。',
        'string' => ':attribute 必須大於或等於 :value 個字元。',
    ],
    'image' => ':attribute 必須是圖片。',
    'in' => '選擇的 :attribute 無效。',
    'in_array' => ':attribute 欄位不存在於 :other 中。',
    'integer' => ':attribute 必須是整數。',
    'ip' => ':attribute 必須是有效的 IP 地址。',
    'ipv4' => ':attribute 必須是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必須是有效的 IPv6 地址。',
    'json' => ':attribute 必須是有效的 JSON 字串。',
    'lowercase' => ':attribute 必須是小寫。',
    'lt' => [
        'array' => ':attribute 必須少於 :value 個項目。',
        'file' => ':attribute 必須小於 :value KB。',
        'numeric' => ':attribute 必須小於 :value。',
        'string' => ':attribute 必須少於 :value 個字元。',
    ],
    'lte' => [
        'array' => ':attribute 不能有超過 :value 個項目。',
        'file' => ':attribute 必須小於或等於 :value KB。',
        'numeric' => ':attribute 必須小於或等於 :value。',
        'string' => ':attribute 必須小於或等於 :value 個字元。',
    ],
    'mac_address' => ':attribute 必須是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 不能有超過 :max 個項目。',
        'file' => ':attribute 不能大於 :max KB。',
        'numeric' => ':attribute 不能大於 :max。',
        'string' => ':attribute 不能超過 :max 個字元。',
    ],
    'max_digits' => ':attribute 不能有超過 :max 位數字。',
    'mimes' => ':attribute 必須是 :values 類型的檔案。',
    'mimetypes' => ':attribute 必須是 :values 類型的檔案。',
    'min' => [
        'array' => ':attribute 至少必須有 :min 個項目。',
        'file' => ':attribute 至少必須有 :min KB。',
        'numeric' => ':attribute 至少必須是 :min。',
        'string' => ':attribute 至少必須有 :min 個字元。',
    ],
    'min_digits' => ':attribute 至少必須有 :min 位數字。',
    'missing' => ':attribute 欄位必須缺少。',
    'missing_if' => '當 :other 為 :value 時，:attribute 欄位必須缺少。',
    'missing_unless' => '除非 :other 為 :value，否則 :attribute 欄位必須缺少。',
    'missing_with' => '當存在 :values 時，:attribute 欄位必須缺少。',
    'missing_with_all' => '當存在 :values 時，:attribute 欄位必須缺少。',
    'multiple_of' => ':attribute 必須是 :value 的倍數。',
    'not_in' => '選擇的 :attribute 無效。',
    'not_regex' => ':attribute 格式無效。',
    'numeric' => ':attribute 必須是數字。',
    'password' => [
        'letters' => ':attribute 必須包含至少一個字母。',
        'mixed' => ':attribute 必須包含至少一個大寫和一個小寫字母。',
        'numbers' => ':attribute 必須包含至少一個數字。',
        'symbols' => ':attribute 必須包含至少一個符號。',
        'uncompromised' => '給定的 :attribute 出現在資料洩露中。請選擇不同的 :attribute。',
    ],
    'present' => ':attribute 欄位必須存在。',
    'prohibited' => ':attribute 欄位被禁止。',
    'prohibited_if' => '當 :other 為 :value 時，:attribute 欄位被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位被禁止。',
    'prohibits' => ':attribute 欄位禁止 :other 存在。',
    'regex' => ':attribute 格式無效。',
    'required' => ':attribute 欄位為必填。',
    'required_array_keys' => ':attribute 欄位必須包含以下項目：:values。',
    'required_if' => '當 :other 為 :value 時，:attribute 欄位為必填。',
    'required_if_accepted' => '當 :other 被接受時，:attribute 欄位為必填。',
    'required_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位為必填。',
    'required_with' => '當存在 :values 時，:attribute 欄位為必填。',
    'required_with_all' => '當存在 :values 時，:attribute 欄位為必填。',
    'required_without' => '當不存在 :values 時，:attribute 欄位為必填。',
    'required_without_all' => '當不存在任何 :values 時，:attribute 欄位為必填。',
    'same' => ':attribute 和 :other 必須相符。',
    'size' => [
        'array' => ':attribute 必須包含 :size 個項目。',
        'file' => ':attribute 必須是 :size KB。',
        'numeric' => ':attribute 必須是 :size。',
        'string' => ':attribute 必須是 :size 個字元。',
    ],
    'starts_with' => ':attribute 必須以下列之一開頭：:values。',
    'string' => ':attribute 必須是字串。',
    'timezone' => ':attribute 必須是有效的時區。',
    'unique' => ':attribute 已經被使用。',
    'uploaded' => ':attribute 上傳失敗。',
    'uppercase' => ':attribute 必須是大寫。',
    'url' => ':attribute 必須是有效的網址。',
    'ulid' => ':attribute 必須是有效的 ULID。',
    'uuid' => ':attribute 必須是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | 自定義驗證語言檔案
    |--------------------------------------------------------------------------
    |
    | 在此處您可以為屬性指定自定義驗證訊息，使用
    | "attribute.rule" 的約定來命名行。這使得快速
    | 為給定屬性規則指定特定的自定義語言行變得容易。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => '自定義訊息',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定義驗證屬性
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於將我們的屬性佔位符替換為更易讀的內容，
    | 例如 "E-Mail Address" 而不是 "email"。這只是幫助我們讓我們的訊息更具表達力。
    |
    */

    'attributes' => [
        'username' => '使用者名稱',
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'password_confirmation' => '確認密碼',
        'role' => '角色',
        'roles' => '角色',
        'permission' => '權限',
        'permissions' => '權限',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'status' => '狀態',
        'created_at' => '建立時間',
        'updated_at' => '更新時間',
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定義驗證語言行
    |--------------------------------------------------------------------------
    |
    | 在此處您可以為特定屬性指定自定義驗證訊息，使用
    | "attribute.rule" 的約定來命名行。這使得為給定的屬性規則
    | 指定特定的自定義語言行變得快速。
    |
    */

    'custom' => [
        'name' => [
            'regex' => '權限名稱格式無效，請使用模組.動作格式（例如：users.view）',
            'unique' => '此權限名稱已存在',
        ],
        'dependencies' => [
            'array' => '依賴權限必須是陣列格式',
        ],
        'dependencies.*' => [
            'exists' => '選擇的依賴權限不存在',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定義驗證屬性
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於將屬性佔位符替換為更易讀的內容，
    | 例如將 "email" 替換為 "電子郵件地址"。這只是為了讓我們的訊息更具表達力。
    |
    */

    'attributes' => [
        'name' => '權限名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'module' => '模組',
        'type' => '權限類型',
        'dependencies' => '依賴權限',
    ],

];