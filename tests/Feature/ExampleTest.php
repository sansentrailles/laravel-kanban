<?php

it('redirects guests from the dashboard to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
