<?php

namespace Hoanvv\App\Database;

interface IMasterDatabase
{
    public function query($mysql);
    public function prepare($mysql);
    public function beginTransaction();
    public function rollback();
}
