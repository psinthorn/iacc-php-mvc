# iAcc Project - Quick Reference Card

**Print this and keep at your desk!**

---

## 📋 PROJECT AT A GLANCE

**Name**: iAcc PHP Accounting Management System  
**Status**: Modernization & Production Deployment  
**Timeline**: Jan 1 - Feb 18, 2026  
**Phases**: 4 (Tech Stack → Database → Security → Deployment)  

---

## 🎯 MAIN OBJECTIVES

1. ✅ Upgrade PHP 7.4 → 8.3
2. ✅ Upgrade MySQL 5.7 → 8.0
3. ✅ Migrate passwords: MD5 → bcrypt
4. ✅ Implement RBAC (5 roles)
5. ✅ Deploy to cPanel (zero downtime)
6. ✅ Implement security controls
7. ✅ Automate backups
8. ✅ Setup 24/7 monitoring

---

## 📚 DOCUMENT ROADMAP

### START HERE (read first)
1. **EXECUTIVE_SUMMARY.md** ← You are here  
2. **PROJECT_ROADMAP_2026.md** ← Full 4-phase plan
3. **IMPLEMENTATION_TIMELINE.md** ← Day-by-day schedule

### FOR SPECIFIC PHASES
- **Phase 1**: `docs/UPGRADE_PHP_MYSQL.md`
- **Phase 2**: `PHASE_4_STEP_3_PLANNED.md`
- **Phase 3**: `PHASE_4_STEP_6_PLANNED.md`
- **Phase 4**: `DEPLOYMENT_PLAN_STEPS_1-4.md`

### FOR TESTING
- **Test Suite**: `docs/TESTING_CHECKLIST.md` (29 tests)

### FOR PRODUCTION
- **Deployment**: `DEPLOYMENT_README.md`
- **Staging**: `docs/STAGING_DEPLOYMENT_GUIDE.md`

---

## 📅 TIMELINE AT A GLANCE

```
WEEK 1:  Jan 1-7       → PHASE 1: Tech Stack (PHP 8.3, MySQL 8.0)
WEEK 2-3: Jan 8-21     → PHASE 2: Database (Audit, Backups, Constraints)
WEEK 4:  Jan 22-Feb 4  → PHASE 3: Security (bcrypt, RBAC, Headers)
WEEK 5:  Feb 5-18      → PHASE 4: Deploy to cPanel (Blue-Green)
```

---

## 🔑 KEY FILES & LOCATIONS

### Configuration
- **Database**: `iacc/inc/sys.configs.php`
- **App Config**: `config/app.php`
- **DB Config**: `config/database.php`
- **Env Vars**: `.env` (local), `.env.production` (prod)

### Application Core
- **Router**: `iacc/index.php`
- **DB Class**: `iacc/inc/class.dbconn.php`
- **Auth**: `iacc/authorize.php`, `iacc/login.php`
- **Business Logic**: `iacc/core-function.php`

### Database
- **Latest Schema**: `iacc_26122025.sql`
- **Migrations**: `database/migrations/`
- **Password Migration**: `iacc/PHASE1_MIGRATION.php`

### Security (to create)
- **Security Helper**: `iacc/inc/SecurityHelper.php`
- **CSRF Middleware**: `iacc/middleware/CsrfToken.php`
- **Session Timeout**: `iacc/middleware/SessionTimeout.php`

---

## 🔄 PHASE QUICK REFERENCE

### PHASE 1: Tech Stack (7 days)
**Team**: DevOps Lead + QA  
**Main Tasks**:
- [ ] PHP 8.3 upgrade (cPanel EasyApache)
- [ ] MySQL 8.0 upgrade (cPanel WHM)
- [ ] Run 29-test suite
- [ ] Zero fatal errors confirmed

**Success**: PHP 8.3 + MySQL 8.0 + all tests passing

---

### PHASE 2: Database (14 days)
**Team**: Database Admin + Developer  
**Main Tasks**:
- [ ] Add system columns (created_at, updated_at, etc.)
- [ ] Add foreign key constraints
- [ ] Create audit log system with triggers
- [ ] Automate daily backups
- [ ] Test backup restoration

**Success**: Data integrity + audit trails + backups

---

### PHASE 3: Security (14 days)
**Team**: Security Lead + Developer + QA  
**Main Tasks**:
- [ ] MD5 → bcrypt password migration (100%)
- [ ] RBAC implementation (5 roles, 50+ permissions)
- [ ] Session security (timeout, CSRF, secure cookies)
- [ ] Input validation + prepared statements
- [ ] Security headers on all responses

**Success**: OWASP Top 10 = 0 vulnerabilities

---

### PHASE 4: Deployment (14 days)
**Team**: DevOps Lead + Developer + QA + Database Admin  
**Main Tasks**:
- [ ] Prepare green environment
- [ ] Deploy code to cPanel
- [ ] Run smoke tests
- [ ] Blue-Green deployment switch
- [ ] Monitor 24+ hours
- [ ] Setup 24/7 monitoring & alerts

**Success**: Production deployment, 99.9% uptime

---

## ✅ GO/NO-GO GATES

| Gate | Date | Approval | Criteria |
|------|------|----------|----------|
| Phase 1 | Jan 7 | DevOps Lead | All tests pass ✓ |
| Phase 2 | Jan 21 | DB Admin | All constraints working ✓ |
| Phase 3 | Feb 4 | Security Lead | OWASP scan: 0 critical ✓ |
| Phase 4 | Feb 18 | Project Lead | 99.9% uptime + monitoring ✓ |

---

## 🚨 CRITICAL DO's AND DON'Ts

### ✅ DO:
- ✅ Read historical documents before making changes
- ✅ Test in staging (Docker) before production
- ✅ Use git for all code changes
- ✅ Back up before every database change
- ✅ Run full test suite before deployment
- ✅ Monitor for 24+ hours after deployment
- ✅ Document all changes with commit messages

### ❌ DON'T:
- ❌ Modify production code directly
- ❌ Run migrations without backup
- ❌ Skip test suite
- ❌ Use version control for passwords
- ❌ Deploy without team approval
- ❌ Ignore error logs after deployment
- ❌ Skip documentation

---

## 📞 QUICK CONTACTS

| Role | Contact | Escalation |
|------|---------|------------|
| **DevOps Lead** | [Name] | Infrastructure/cPanel issues |
| **Database Admin** | [Name] | Database/Schema issues |
| **Security Lead** | [Name] | Security/OWASP issues |
| **Senior Dev** | [Name] | Code/Logic issues |
| **QA Manager** | [Name] | Testing/QA issues |
| **Project Lead** | [Name] | Go/No-Go decisions |

---

## 💡 QUICK ANSWERS

**Q: Where's the database schema?**  
A: `iacc_26122025.sql` (latest) or `PHASE_4_STEP_3_PLANNED.md`

**Q: How do I test locally?**  
A: `docker compose up -d` then `http://localhost:8089`

**Q: What's the current PHP version?**  
A: 8.3 (modernized) → Deploy to cPanel during Phase 1

**Q: Are there security issues?**  
A: Yes (MD5 passwords, no CSRF, basic auth) → Fixed in Phase 3

**Q: When can we deploy to cPanel?**  
A: Feb 5 (after Phase 3 complete) → Blue-Green deployment

**Q: How do I handle an emergency?**  
A: Check `IMPLEMENTATION_TIMELINE.md` for rollback procedures

**Q: Where are the migration scripts?**  
A: `database/migrations/` and `iacc/PHASE1_MIGRATION.php`

---

## 🎯 DAILY STANDUP QUESTIONS

**Every day, answer these**:
1. What phase are we in? ← Current week
2. What's the current milestone? ← Go/No-Go gate date
3. Are there blockers? ← Report immediately
4. Did all tests pass? ← Required for progress
5. Are backups running? ← Must verify daily
6. Is monitoring active? ← Phase 4+ only

---

## 📊 SUCCESS CHECKLIST

### Week 1 (Phase 1)
- [ ] PHP 8.3 installed
- [ ] MySQL 8.0 installed
- [ ] All 29 tests pass
- [ ] Zero fatal errors
- [ ] Gate 1: ✅ APPROVED

### Week 2-3 (Phase 2)
- [ ] System columns added
- [ ] Foreign keys working
- [ ] Audit system active
- [ ] Backups automated
- [ ] Gate 2: ✅ APPROVED

### Week 4 (Phase 3)
- [ ] 100% passwords bcrypt
- [ ] RBAC fully implemented
- [ ] CSRF tokens on forms
- [ ] OWASP scan: 0 critical
- [ ] Gate 3: ✅ APPROVED

### Week 5 (Phase 4)
- [ ] Deployed to cPanel
- [ ] Zero downtime achieved
- [ ] All features working
- [ ] Monitoring active
- [ ] Gate 4: ✅ APPROVED
- [ ] 🎉 PROJECT COMPLETE

---

## 🔗 IMPORTANT LINKS

| Resource | URL/Path |
|----------|----------|
| cPanel Access | `https://f2.co.th:2087` |
| Application (Dev) | `http://localhost:8089/iacc/` |
| PhpMyAdmin (Dev) | `http://localhost:8085` |
| Database Dump | `/iacc_26122025.sql` |
| Code Repo | [Your Git Repo] |
| Documentation | `/PROJECT_ROADMAP_2026.md` |
| Tests | `docs/TESTING_CHECKLIST.md` |

---

## 📝 NOTES SECTION

**Space for your team notes**:

```
Date: ___________
Phase: ___________
Status: ___________
Issues: ___________
Next Steps: ___________
```

---

## 🎓 LEARNING RESOURCES

**Before you start**:
1. Read: **EXECUTIVE_SUMMARY.md** (15 min)
2. Read: **PROJECT_ROADMAP_2026.md** (30 min)
3. Review: Historical documents for your phase (varies)
4. Ask: Questions to experienced team members (always ok!)

**During implementation**:
1. Reference: Phase-specific documents (always available)
2. Consult: Database schema and code structure
3. Check: Error logs and monitoring dashboards
4. Communicate: Daily standup with team

**After each phase**:
1. Document: What was accomplished
2. Report: Issues encountered and resolved
3. Review: Lessons learned
4. Update: Documentation if needed

---

**Last Updated**: January 1, 2026  
**Print Date**: ___________  
**Printed By**: ___________
