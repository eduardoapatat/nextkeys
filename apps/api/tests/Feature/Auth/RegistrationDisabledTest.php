<?php

it('does not offer registration on the admin panel', function (string $method) {
    $this->call($method, '/register')->assertNotFound();
})->with(['GET', 'POST']);
