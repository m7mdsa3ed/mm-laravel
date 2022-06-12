<?php

function dispatchAction(App\Actions\Action $action)
{
    return $action->execute();
}
