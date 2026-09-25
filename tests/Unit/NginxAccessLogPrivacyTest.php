<?php

test('production nginx access logs never record request URLs or referers', function () {
    $config = file_get_contents(base_path('deploy/coolify/nginx.template.conf'));

    expect($config)->not->toBeFalse();
    expect(preg_match('/log_format\\s+main\\s+(.+?);/s', $config, $format))->toBe(1);

    preg_match_all('/\\$([a-zA-Z][a-zA-Z0-9_]*)/', $format[1], $matches);
    $actual = array_values(array_unique($matches[1]));
    $allowed = [
        'remote_addr',
        'remote_user',
        'time_local',
        'request_method',
        'server_protocol',
        'status',
        'body_bytes_sent',
        'http_user_agent',
        'http_x_forwarded_for',
    ];

    sort($actual);
    sort($allowed);

    expect($actual)->toBe($allowed)
        ->and($format[1])->toContain('[path-redacted]');
});
