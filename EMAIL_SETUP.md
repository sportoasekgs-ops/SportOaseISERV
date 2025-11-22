# SportOase IServ Module - Email Configuration Guide

## SMTP Email Setup for Production

The SportOase module sends automatic email notifications when bookings are created or modified. This requires proper SMTP configuration.

## Configuration Steps

### Step 1: Choose an Email Provider

The module supports any SMTP server. Common options:

#### Option A: Gmail (Recommended for Development/Testing)
- **Pros:** Free, easy setup, reliable
- **Cons:** Daily sending limits (500 emails/day), requires App Password
- **Best for:** Small schools, testing

#### Option B: Microsoft 365 / Outlook
- **Pros:** Often already available at schools
- **Cons:** More complex authentication
- **Best for:** Schools with existing Microsoft infrastructure

#### Option C: SendGrid / Mailgun / AWS SES
- **Pros:** Professional, high limits, better deliverability
- **Cons:** Requires account creation, may have costs
- **Best for:** Large schools, production deployments

### Step 2: Configure Environment Variables

Edit `/etc/iserv/sportoase.env` (or wherever IServ stores module env vars):

```bash
# Basic SMTP Configuration
MAILER_DSN=smtp://username:password@smtp.gmail.com:587

# Sender Information (shows in "From" field)
MAILER_FROM_ADDRESS=sportoase.kg@gmail.com
MAILER_FROM_NAME="SportOase Buchungssystem"

# Admin Email (receives all booking notifications)
ADMIN_EMAIL=sportoase.kg@gmail.com
```

### Step 3: Provider-Specific Setup

#### Gmail Setup

1. **Enable 2-Factor Authentication** on your Google Account
2. **Generate App Password:**
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the 16-character password
3. **Configure MAILER_DSN:**
   ```bash
   MAILER_DSN=smtp://your.email@gmail.com:APP_PASSWORD_HERE@smtp.gmail.com:587
   ```

**Example:**
```bash
MAILER_DSN=smtp://sportoase.kg@gmail.com:abcd efgh ijkl mnop@smtp.gmail.com:587
```

**Important:** Remove spaces from the App Password!
```bash
# Correct:
MAILER_DSN=smtp://user:abcdefghijklmnop@smtp.gmail.com:587

# Wrong:
MAILER_DSN=smtp://user:abcd efgh ijkl mnop@smtp.gmail.com:587
```

#### Microsoft 365 / Outlook.com Setup

```bash
MAILER_DSN=smtp://your.email@outlook.com:your_password@smtp-mail.outlook.com:587
```

#### SendGrid Setup

1. Create account at https://sendgrid.com
2. Generate API Key
3. Configure:
   ```bash
   MAILER_DSN=smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587
   ```

#### Mailgun Setup

1. Create account at https://mailgun.com
2. Get SMTP credentials from domain settings
3. Configure:
   ```bash
   MAILER_DSN=smtp://postmaster@your-domain.mailgun.org:API_KEY@smtp.mailgun.org:587
   ```

### Step 4: Test Email Configuration

After configuration, test the email system:

```bash
# Run email test script
php bin/console app:test-email sportoase.kg@gmail.com
```

You should receive a test email at the specified address.

## Email Templates

The module sends emails in these scenarios:

### 1. New Booking Created
**Subject:** "Neue Buchung: [Date] - Periode [Period]"

**Content:**
```
Neue Buchung erstellt:

Datum: [Date]
Periode: [Period Number]
Lehrkraft: [Teacher Name] ([Teacher Class])
Angebot: [Offer Label]
Schüler: [Student List]
```

### 2. Booking Modified
**Subject:** "Buchung geändert: [Date] - Periode [Period]"

**Content:** Similar to new booking, with change highlights

### 3. Booking Cancelled
**Subject:** "Buchung storniert: [Date] - Periode [Period]"

## Troubleshooting

### Common Issues

#### Error: "Failed to authenticate on SMTP server"
**Solution:**
- Double-check username and password
- For Gmail: Ensure you're using an App Password, not your regular password
- For Gmail: Enable "Less secure app access" (if not using App Password)

#### Error: "Connection could not be established with host smtp.gmail.com"
**Solution:**
- Check firewall rules (port 587 must be open)
- Try port 465 with SSL:
  ```bash
  MAILER_DSN=smtp://user:pass@smtp.gmail.com:465?encryption=ssl
  ```

#### Emails go to spam folder
**Solution:**
- Add SPF record to your domain
- Use a professional email provider (SendGrid/Mailgun)
- Ask recipients to whitelist the sender address

#### Emails not being received at all
**Solution:**
- Check logs: `tail -f /var/log/iserv/sportoase.log`
- Verify ADMIN_EMAIL is correctly set
- Test with a different email address

### Debug Mode

Enable detailed email debug logging:

```bash
# Add to .env
MAILER_DEBUG=1
```

Then check logs:
```bash
tail -f /var/log/iserv/sportoase-mail.log
```

## Security Best Practices

### 1. Use App Passwords (Gmail)
Never use your main Google account password. Always use App Passwords.

### 2. Environment Variable Security
Ensure `.env` files are not world-readable:
```bash
chmod 600 /etc/iserv/sportoase.env
chown www-data:www-data /etc/iserv/sportoase.env
```

### 3. TLS/SSL Encryption
Always use encrypted connections (port 587 with STARTTLS or port 465 with SSL).

### 4. Rotate Credentials
Change SMTP passwords regularly, especially after staff changes.

## Production Checklist

- [ ] SMTP credentials configured
- [ ] Test email successfully sent
- [ ] Sender address matches school domain (for better deliverability)
- [ ] Admin email receives notifications
- [ ] Emails don't go to spam folder
- [ ] Logs show successful email delivery
- [ ] Environment variables are properly secured (chmod 600)

## Alternative: Disable Email Notifications

If email is not required, you can disable notifications:

```bash
# In .env
MAILER_ENABLED=false
```

The module will continue to work but won't send emails.

## Support

For email configuration issues:
- **General:** Check Symfony Mailer docs: https://symfony.com/doc/current/mailer.html
- **Module-specific:** Contact sportoase.kg@gmail.com
