# Easy Certificate (mod_easycertificate)

Creates customizable digital certificates directly inside Moodle courses.

---

## Requirements

- Moodle 5.0+
- PHP 8.1+
- OpenSSL extension enabled for digital signatures

---

## Installation

1. Copy the plugin folder to:

```text
mod/easycertificate
```

2. Access:

```text
Site administration → Notifications
```

3. Complete the installation process.

---

## Features

### Certificate Templates
- Visual certificate editor
- Multiple pages
- Landscape and portrait orientation
- Background image support
- Image and text elements
- Element positioning and resizing
- Zoom controls
- Reusable global templates

### Dynamic Fields
Supports automatic replacement of placeholders such as:

- `{firstname}`
- `{lastname}`
- `{fullname}`
- `{email}`
- `{course}`
- `{courseshortname}`
- `{issuedate}`
- `{completiondate}`

Custom user profile fields are also supported.

### Certificate Issuance
- Student self-access
- PDF generation
- Inline preview
- Certificate download
- Unique issue records

### Digital Signatures
- PFX / P12 certificate support
- Incremental PDF signatures
- Visual signature masks
- Multiple signatures per document

---

## Template Management

Global templates can be managed at:

```text
Site administration → Plugins → Activity modules → Easy Certificate
```

Teachers can select a template directly in the activity configuration.

---

## Capabilities

- `mod/easycertificate:addinstance`
- `mod/easycertificate:view`
- `mod/easycertificate:manage`
- `mod/easycertificate:managetemplates`

---

## Backup and Restore

The plugin supports Moodle course backup and restore, including:

- Activity settings
- Linked templates
- Issued certificates (when user data is included)

---

## Data and Privacy (GDPR)

The plugin stores certificate issue records required for certificate validation and history.

### Stored data

- User ID
- Certificate ID
- Issue code
- Issue date

### Privacy API

Implements:

- `\core_privacy\local\metadata\null_provider`

---

## Notes

- PDF signature support depends on the server OpenSSL configuration.
- Some browsers may handle inline PDF previews differently.
- Recommended for use with HTTPS-enabled Moodle environments.

---

## Version

Current release:

```text
1.0.1
```

---

## License

GNU GPL v3 or later.
