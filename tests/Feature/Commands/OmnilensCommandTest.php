<?php

it('should run the command', function () {
    $this->artisan('lens:install')->expectsConfirmation('Would you like to run the migrations now?')->expectsConfirmation('Would you like to star our repo on GitHub?')->assertExitCode(0);
});
