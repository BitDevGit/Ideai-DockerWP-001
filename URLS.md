# WordPress URLs Reference

## 🌐 Local Development

**Site URL:**  
http://localhost

**Admin URL:**  
http://localhost/wp-admin

**WordPress Installation:**  
http://localhost (redirects to installation if not set up)

---

## ☁️ AWS Lightsail Production

**Instance Name:** `wordpress-multisite`  
**Region:** eu-west-2 (London)  
**IP Address:** `13.40.170.117`

**Site URL:**  
http://13.40.170.117

**Admin URL:**  
http://13.40.170.117/wp-admin

**Status:** ✅ All containers running

---

## 🔄 Database Migration

When migrating from local to AWS:

**Old Domain:** `localhost`  
**New Domain:** `13.40.170.117`

**Migration Command:**
```bash
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
```

---

## 📝 Quick Reference

| Environment | URL | Admin URL |
|------------|-----|-----------|
| **Local** | http://localhost | http://localhost/wp-admin |
| **AWS** | http://13.40.170.117 | http://13.40.170.117/wp-admin |

---

## 🔗 Testing

**Test Theme:**  
- Local: http://localhost (activate "Test Cursor Theme")
- AWS: http://13.40.170.117 (activate "Test Cursor Theme")

**Expected:** "Hello Cursor!" message on homepage

---

## 📌 Notes

- Local uses Docker volume mounts (instant changes)
- AWS uses Docker image with wp-content included
- Both use same wp-content structure
- Database migration handles URL rewrites automatically



