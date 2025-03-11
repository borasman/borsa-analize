<?php

// router.php
if (is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'])) {
    return false;
} else {
    include_once 'index.php';
} 