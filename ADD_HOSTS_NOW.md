# Add Hosts Entries - REQUIRED

## ⚠️ Error: ERR_NAME_NOT_RESOLVED

This means your computer can't find `site1.local` and `site2.local`.

## ✅ Quick Fix

### Option 1: Terminal (Recommended)

Open Terminal and run:

```bash
sudo sh -c 'echo "127.0.0.1  site1.local" >> /etc/hosts'
sudo sh -c 'echo "127.0.0.1  site2.local" >> /etc/hosts'
```

You'll be asked for your password - enter it.

### Option 2: Manual Edit

1. Open Terminal
2. Run: `sudo nano /etc/hosts`
3. Enter your password
4. Add these two lines at the end:
   ```
   127.0.0.1  site1.local
   127.0.0.1  site2.local
   ```
5. Press `Ctrl+X`, then `Y`, then `Enter` to save

### Option 3: Using TextEdit (macOS)

1. Open Terminal
2. Run: `sudo open -a TextEdit /etc/hosts`
3. Enter your password
4. Add these two lines at the end:
   ```
   127.0.0.1  site1.local
   127.0.0.1  site2.local
   ```
5. Save and close

## ✅ Verify

After adding, test:

```bash
ping -c 1 site1.local
```

Should show: `64 bytes from 127.0.0.1`

## 🚀 Then Access

- **Dashboard:** http://localhost
- **Site 1:** http://site1.local
- **Site 2:** http://site2.local


