<?php

/*
| Backups of everything in the system: the whole database plus every uploaded file (student profile pictures,
| certificates, resumes, company logos and photos), in one archive encrypted with AES-256
| (App\Services\BackupArchive). A backup is never written without a password.
*/
return [
    // The password that encrypts every backup. At least 16 characters. Keep a copy somewhere other than this
    // server (a password manager): without it a backup cannot be opened, and anyone who has it can open them.
    'password' => env('BACKUP_PASSWORD'),

    // A second copy of every backup, in a folder on another drive or a synced cloud folder (OneDrive, Google
    // Drive). Leave empty to keep only the copy in storage/app/backups.
    'copy_path' => env('BACKUP_COPY_PATH'),

    // How many archives to keep, in each place.
    'keep' => 30,

    // Uploaded files that go into the archive: storage disk => folder inside the archive.
    'disks' => [
        'public' => 'files/public',
        'private' => 'files/private',
    ],
];
