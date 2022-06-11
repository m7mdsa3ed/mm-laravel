<?php

function dispatchAction(App\Actions\Action $action)
{
    $action->execute();
}
