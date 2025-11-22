# SportOase IServ Module - Build & Deployment Instructions

## Building the Debian Package

### Prerequisites

On your build machine, you need:
- Debian/Ubuntu Linux (or WSL on Windows)
- `build-essential` package
- `debhelper` (>= 11)
- `nodejs` and `npm`
- `composer`

Install prerequisites:
```bash
sudo apt update
sudo apt install -y build-essential debhelper nodejs npm composer php-cli php-xml php-mbstring
```

### Build Steps

#### 1. Clone the Repository

```bash
git clone https://github.com/sportoase/iserv-sportoase.git
cd iserv-sportoase
```

#### 2. Build the Debian Package

```bash
# This will:
#  - Install PHP dependencies with composer
#  - Install Node.js dependencies
#  - Compile Tailwind CSS assets
#  - Create the .deb package
dpkg-buildpackage -us -uc -b
```

The build process takes ~2-5 minutes depending on your machine.

#### 3. Locate the Package

After successful build, the `.deb` file will be in the parent directory:

```bash
cd ..
ls -lh iserv-sportoase_*.deb
```

Example output:
```
-rw-r--r-- 1 user user 15M Nov 22 15:00 iserv-sportoase_1.0.0_all.deb
```

## Installing on IServ

### Method 1: Via IServ Admin Panel (Recommended)

1. **Upload the Package:**
   - Log into IServ as admin
   - Go to `Administration` → `Modules` → `Install Module`
   - Upload `iserv-sportoase_1.0.0_all.deb`
   - Click `Install`

2. **Configure OAuth2:**
   - Go to `Administration` → `Module Settings` → `SportOase`
   - Create OAuth2 Application:
     - Name: `SportOase Booking System`
     - Redirect URI: `https://your-iserv-instance.de/sportoase/oauth/callback`
   - Copy Client ID and Client Secret

3. **Configure Environment:**
   ```bash
   sudo nano /etc/iserv/sportoase.env
   ```
   
   Update:
   ```bash
   ISERV_OAUTH_CLIENT_ID=your-client-id
   ISERV_OAUTH_CLIENT_SECRET=your-client-secret
   ISERV_BASE_URL=https://your-iserv-instance.de
   
   MAILER_DSN=smtp://your-email@gmail.com:app-password@smtp.gmail.com:587
   MAILER_FROM_ADDRESS=sportoase@your-school.de
   ADMIN_EMAIL=admin@your-school.de
   ```

4. **Verify Installation:**
   ```bash
   # Check if tables were created
   sudo -u postgres psql iserv_database -c "\dt sportoase_*"
   
   # Should show 5 tables:
   # sportoase_users
   # sportoase_bookings
   # sportoase_slot_names
   # sportoase_blocked_slots
   # sportoase_notifications
   ```

5. **Access the Module:**
   - Go to `https://your-iserv-instance.de/sportoase`
   - Login with IServ credentials
   - You should see the SportOase dashboard

### Method 2: Via Command Line

```bash
# Copy the .deb to your IServ server
scp iserv-sportoase_1.0.0_all.deb admin@your-iserv-instance.de:/tmp/

# SSH into IServ server
ssh admin@your-iserv-instance.de

# Install the package
sudo dpkg -i /tmp/iserv-sportoase_1.0.0_all.deb

# If there are dependency issues:
sudo apt-get install -f

# Configure as described in Method 1, steps 2-5
```

## Updating the Module

### Build New Version

1. Update version in `debian/changelog`
2. Rebuild package: `dpkg-buildpackage -us -uc -b`
3. Install updated package on IServ

### Update Without Rebuilding (Development)

For quick updates during development:

```bash
# On your development machine, sync files to IServ
rsync -avz --exclude node_modules --exclude vendor \
  ./ admin@your-iserv:/usr/share/iserv/modules/sportoase/

# SSH into IServ and rebuild assets
ssh admin@your-iserv
cd /usr/share/iserv/modules/sportoase
sudo npm run build
sudo /usr/sbin/iserv-reload
```

## Troubleshooting Build Issues

### Error: "npm: command not found"

```bash
sudo apt install -y nodejs npm
```

### Error: "composer: command not found"

```bash
sudo apt install -y composer
```

### Error: "debian/rules: Permission denied"

```bash
chmod +x debian/rules debian/postinst debian/postrm
```

### Error: Build fails during npm install

Check Node.js version (needs >= 16):
```bash
node --version  # Should be v16+ or v18+

# If too old, install newer version:
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Error: Webpack compilation fails

Clean and retry:
```bash
rm -rf node_modules public/build
npm install
npm run build
```

## Development Workflow

### Local Development

1. **Start Dev Server:**
   ```bash
   npm run watch  # Auto-recompile on file changes
   php -S localhost:8000 -t public/
   ```

2. **Make Changes:**
   - Edit templates in `templates/sportoase/`
   - Edit CSS in `assets/styles/app.css`
   - Edit PHP in `src/`

3. **Test Changes:**
   - View at `http://localhost:8000`

4. **Build for Production:**
   ```bash
   npm run build
   ```

### Testing the Package Before Deployment

Test the built package in a VM before deploying to production:

```bash
# Install in a test VM
vagrant init debian/bullseye64
vagrant up
vagrant ssh

# Inside VM:
sudo dpkg -i /path/to/iserv-sportoase_1.0.0_all.deb
```

## CI/CD Integration

### GitHub Actions Example

Create `.github/workflows/build.yml`:

```yaml
name: Build Debian Package

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Install dependencies
      run: |
        sudo apt update
        sudo apt install -y build-essential debhelper nodejs npm composer
    
    - name: Build package
      run: dpkg-buildpackage -us -uc -b
    
    - name: Upload artifact
      uses: actions/upload-artifact@v3
      with:
        name: debian-package
        path: ../iserv-sportoase_*.deb
```

## File Locations After Installation

| File/Directory | Location on IServ |
|----------------|-------------------|
| Source code | `/usr/share/iserv/modules/sportoase/src/` |
| Templates | `/usr/share/iserv/modules/sportoase/templates/` |
| Compiled assets | `/usr/share/iserv/modules/sportoase/public/build/` |
| Configuration | `/etc/iserv/sportoase.env` |
| Cache | `/var/cache/iserv/sportoase/` |
| Logs | `/var/log/iserv/sportoase/` |
| Documentation | `/usr/share/doc/iserv-sportoase/` |

## Checklist

### Before Building:
- [ ] All code changes committed to git
- [ ] Version updated in `debian/changelog`
- [ ] Tests passing (if applicable)
- [ ] Documentation updated

### After Building:
- [ ] `.deb` file created successfully
- [ ] Package size reasonable (< 20MB)
- [ ] Test installation in VM

### Before Deploying to Production:
- [ ] OAuth2 credentials configured
- [ ] SMTP email tested
- [ ] Database backup created
- [ ] Rollback plan ready

## Support

For build issues:
- **General Debian packaging:** https://www.debian.org/doc/manuals/maint-guide/
- **Symfony:** https://symfony.com/doc/
- **Webpack Encore:** https://symfony.com/doc/current/frontend.html
- **SportOase-specific:** sportoase.kg@gmail.com
