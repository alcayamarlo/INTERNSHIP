<?php

/*
 * Header text and seals printed on the school's OJT forms (Monthly Evaluation, and later the weekly
 * report and final evaluation). A seal is printed only when its file exists, so a missing file never
 * breaks the layout and no placeholder is shown.
 */
return [
    'school_name' => "ST. CECILIA'S COLLEGE – CEBU, INC.",
    'school_supervision' => 'LASALLIAN SCHOOLS SUPERVISION OFFICE (LASSO) SUPERVISED SCHOOL',
    'school_address' => 'N. Bacalso S National Highway, Ward II, Poblacion, Minglanilla, Cebu',
    'college_name' => 'COLLEGE OF INFORMATION TECHNOLOGY',

    // Paths under public/. Any of .png, .jpg, .jpeg or .webp is accepted for the same base name.
    'school_seal' => 'images/seals/scc-seal',
    'college_seal' => 'images/seals/cit-seal',
];
