<?php
// JWT configuration file
return [
    // TODO: For production, use getenv('JWT_SECRET') or a .env loader
    'secret' => 'REPLACE_THIS_WITH_A_SECRET_KEY',
    'algo' => 'HS256',
    // You can add more options here (issuer, audience, etc.)
];
