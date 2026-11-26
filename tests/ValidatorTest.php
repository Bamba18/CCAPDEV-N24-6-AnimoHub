<?php
// Unit tests for security/validator.php (all functions of the Validator class).
// No libraries needed. Run from the project root with:
//     php tests/ValidatorTest.php
// Save the output into tests/test-results.txt:
//     php tests/ValidatorTest.php > tests/test-results.txt
require_once __DIR__ . '/../security/validator.php';

$passed = 0;
$failed = 0;

// small helper: compares what we got with what we expected
function check($name, $got, $expected) {
    global $passed, $failed;
    if ($got === $expected) {
        $passed++;
        echo "PASS  $name\n";
    } else {
        $failed++;
        echo "FAIL  $name (expected " . var_export($expected, true) . ", got " . var_export($got, true) . ")\n";
    }
}

// required()
check('required: normal text',        Validator::required('hello', 'Title'), null);
check('required: empty string',       Validator::required('', 'Title'), 'Title is required');
check('required: spaces only',        Validator::required('   ', 'Title'), 'Title is required');

// maxLength()
check('maxLength: short enough',      Validator::maxLength('abc', 5, 'Title'), null);
check('maxLength: exactly the limit', Validator::maxLength('abcde', 5, 'Title'), null);
check('maxLength: too long',          Validator::maxLength('abcdef', 5, 'Title'), 'Title must be at most 5 characters');

// date()
check('date: valid date',             Validator::date('2026-11-30'), null);
check('date: fake day (Feb 31)',      Validator::date('2026-02-31'), 'Date is not a valid date');
check('date: wrong format',           Validator::date('30/11/2026'), 'Date is not a valid date');
check('date: empty',                  Validator::date(''), 'Date is not a valid date');

// time()
check('time: valid time',             Validator::time('14:30'), null);
check('time: empty is allowed',       Validator::time(''), null);
check('time: hour 25',                Validator::time('25:00'), 'Time is not a valid time');
check('time: letters',                Validator::time('abc'), 'Time is not a valid time');

// inList()
check('inList: allowed value',        Validator::inList('public', ['public', 'members'], 'Visibility'), null);
check('inList: not allowed value',    Validator::inList('secret', ['public', 'members'], 'Visibility'), 'Visibility has an invalid choice');

// firstError()
check('firstError: all fine',         Validator::firstError([null, null]), null);
check('firstError: gives first one',  Validator::firstError([null, 'first', 'second']), 'first');

echo "\nPassed: $passed   Failed: $failed\n";
exit($failed > 0 ? 1 : 0);
