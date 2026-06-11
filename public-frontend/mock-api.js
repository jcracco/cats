/**
 * mock-api.js — Demo mode API
 * Intercepts all api() calls and runs against in-memory seed data.
 * State persists in sessionStorage for the browser session.
 * Loaded only on the demo domain — overrides the real api() function.
 */

// ── Seed data ─────────────────────────────────────────────────────────────────
const SEED_APPS = [
  {
    "id": 1,
    "date_applied": "2026-05-04",
    "company": "Nexus Analytics",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Technical Program Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "TPM",
    "rating": 85,
    "status": "Interviewing",
    "job_id": null,
    "job_link": "https://example.com/jobs/1",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": "Sarah Chen",
    "cover_letter": 1,
    "has_outreach": 1,
    "outreach_notes": "Sarah Chen via LinkedIn",
    "notes": "Strong fit \u2014 distributed team experience resonated",
    "job_description": "We are looking for a Technical Program Manager to lead delivery...\n\nResponsibilities:\n- Own delivery roadmap across 3 engineering teams\n- Build capacity planning frameworks\n- Drive sprint ceremonies and retrospectives\n\nRequirements:\n- 5+ years TPM or delivery management experience\n- Strong JIRA expertise\n- Experience with distributed teams",
    "timeline_id": 1
  },
  {
    "id": 2,
    "date_applied": "2026-05-11",
    "company": "BlueRidge Software",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Technical Program Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "TPM",
    "rating": 78,
    "status": "Interviewing",
    "job_id": null,
    "job_link": "https://example.com/jobs/1",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": "Sarah Chen",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "Strong fit \u2014 distributed team experience resonated",
    "job_description": "We are looking for a Technical Program Manager to lead delivery...\n\nResponsibilities:\n- Own delivery roadmap across 3 engineering teams\n- Build capacity planning frameworks\n- Drive sprint ceremonies and retrospectives\n\nRequirements:\n- 5+ years TPM or delivery management experience\n- Strong JIRA expertise\n- Experience with distributed teams",
    "timeline_id": 2
  },
  {
    "id": 3,
    "date_applied": "2026-04-29",
    "company": "Meridian Health Tech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "SPO",
    "rating": 82,
    "status": "Interviewing",
    "job_id": null,
    "job_link": "https://example.com/jobs/1",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": "Sarah Chen",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "Strong fit \u2014 distributed team experience resonated",
    "job_description": "We are looking for a Technical Program Manager to lead delivery...\n\nResponsibilities:\n- Own delivery roadmap across 3 engineering teams\n- Build capacity planning frameworks\n- Drive sprint ceremonies and retrospectives\n\nRequirements:\n- 5+ years TPM or delivery management experience\n- Strong JIRA expertise\n- Experience with distributed teams",
    "timeline_id": 3
  },
  {
    "id": 4,
    "date_applied": "2026-04-14",
    "company": "Pinnacle HR Tech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Referral",
    "applied_through": "Email",
    "resume_version": "SPO",
    "rating": 88,
    "status": "Offer",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "135",
    "salary_listed": "135-145",
    "salary_type": "Yearly",
    "contacts": "Rachel Moore \u2014 Recruiter",
    "cover_letter": 1,
    "has_outreach": 1,
    "outreach_notes": "Rachel Moore via LinkedIn",
    "notes": "Offer received \u2014 evaluating",
    "job_description": null,
    "timeline_id": 4
  },
  {
    "id": 5,
    "date_applied": "2026-04-05",
    "company": "Apex Fintech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "SPO",
    "rating": 75,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/5",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 6,
    "date_applied": "2026-04-23",
    "company": "Harbor Digital",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Agile Delivery Lead",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "TPM",
    "rating": 80,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/6",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "120-140",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 7,
    "date_applied": "2026-04-29",
    "company": "Luminary SaaS",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Sr. Product Owner",
    "location_type": "Hybrid",
    "hybrid_location": "Boston",
    "days_onsite": "2-3",
    "source": "LinkedIn",
    "applied_through": "Workday",
    "resume_version": "SPO",
    "rating": 72,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/7",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "140-160",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 8,
    "date_applied": "2026-04-24",
    "company": "Summit Data Co",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Technical Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Indeed",
    "applied_through": "Greenhouse",
    "resume_version": "TPM",
    "rating": 68,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/8",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "115-135",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 9,
    "date_applied": "2026-05-08",
    "company": "Orbit Software",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Program Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Company website",
    "applied_through": "iCIMS",
    "resume_version": "TPM",
    "rating": 92,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/9",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "125-145",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 1,
    "outreach_notes": "Marcus Webb via LinkedIn",
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 10,
    "date_applied": "2026-05-08",
    "company": "Pinnacle HR Tech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Product Manager",
    "location_type": "Hybrid",
    "hybrid_location": "Cambridge",
    "days_onsite": "2-3",
    "source": "Recruiter Outreach",
    "applied_through": "Indeed",
    "resume_version": "SPO",
    "rating": 70,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/10",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "135-155",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 11,
    "date_applied": "2026-05-12",
    "company": "Vantage Analytics",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Software Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Referral",
    "applied_through": "Lever",
    "resume_version": "TPM",
    "rating": 77,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/11",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 12,
    "date_applied": "2026-05-20",
    "company": "Redwood Platforms",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Jobgether",
    "applied_through": "Ashby",
    "resume_version": "SPO",
    "rating": 73,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/12",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "120-145",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 13,
    "date_applied": "2026-04-14",
    "company": "DataCore Solutions",
    "via_recruiting_firm": 1,
    "recruiting_firm": "Talentify Group",
    "job_title": "Agile Delivery Lead",
    "location_type": "Hybrid",
    "hybrid_location": "Waltham",
    "days_onsite": "2-3",
    "source": "BuiltIn",
    "applied_through": "SmartRecruiters",
    "resume_version": "TPM",
    "rating": 81,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/13",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "110-130",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 14,
    "date_applied": "2026-05-23",
    "company": null,
    "via_recruiting_firm": 1,
    "recruiting_firm": "TechForce Partners",
    "job_title": "Sr. Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Dice",
    "applied_through": "Workday",
    "resume_version": "SPO",
    "rating": 58,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/14",
    "dashboard_link": null,
    "salary_requested": "65",
    "salary_listed": "65-75",
    "salary_type": "Hourly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 15,
    "date_applied": "2026-04-18",
    "company": "BlueRidge Software",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Technical Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "Greenhouse",
    "resume_version": "TPM",
    "rating": 79,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/15",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 16,
    "date_applied": "2026-05-24",
    "company": "Apex Fintech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Program Manager",
    "location_type": "Hybrid",
    "hybrid_location": "Somerville",
    "days_onsite": "2-3",
    "source": "LinkedIn",
    "applied_through": "Email",
    "resume_version": "TPM",
    "rating": 74,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/16",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 1,
    "has_outreach": 1,
    "outreach_notes": "Hiring manager via LinkedIn",
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 17,
    "date_applied": "2026-05-17",
    "company": "Ironclad Platforms",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Product Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "SPO",
    "rating": 83,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/17",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 18,
    "date_applied": "2026-04-05",
    "company": "Summit Data Co",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Software Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Indeed",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "TPM",
    "rating": 61,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/18",
    "dashboard_link": null,
    "salary_requested": "65",
    "salary_listed": "65-75",
    "salary_type": "Hourly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 19,
    "date_applied": "2026-05-20",
    "company": "DataCore Solutions",
    "via_recruiting_firm": 1,
    "recruiting_firm": "Talentify Group",
    "job_title": "Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Company website",
    "applied_through": "Workday",
    "resume_version": "SPO",
    "rating": 76,
    "status": "Applied",
    "job_id": null,
    "job_link": "https://example.com/jobs/19",
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "140-160",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 20,
    "date_applied": "2026-02-26",
    "company": "Nexus Analytics",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Agile Delivery Lead",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "TPM",
    "rating": 82,
    "status": "Rejected",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "130-150",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": 5
  },
  {
    "id": 21,
    "date_applied": "2026-01-31",
    "company": "BlueRidge Software",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Sr. Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "LinkedIn Easy Apply",
    "resume_version": "SPO",
    "rating": 77,
    "status": "Rejected",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "120-140",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": 6
  },
  {
    "id": 22,
    "date_applied": "2026-04-19",
    "company": "Meridian Health Tech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Technical Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "LinkedIn",
    "applied_through": "Workday",
    "resume_version": "TPM",
    "rating": 71,
    "status": "Ghosted",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "140-160",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": 7
  },
  {
    "id": 23,
    "date_applied": "2026-03-26",
    "company": "Apex Fintech",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Program Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Indeed",
    "applied_through": "Greenhouse",
    "resume_version": "TPM",
    "rating": 79,
    "status": "Ghosted",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "115-135",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": 8
  },
  {
    "id": 24,
    "date_applied": "2026-03-09",
    "company": "Crestview Systems",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Senior Product Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Company website",
    "applied_through": "iCIMS",
    "resume_version": "SPO",
    "rating": 65,
    "status": "Not Selected",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "125-145",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 25,
    "date_applied": "2026-01-31",
    "company": "Harbor Digital",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Software Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Recruiter Outreach",
    "applied_through": "Indeed",
    "resume_version": "TPM",
    "rating": 68,
    "status": "Not Selected",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "135-155",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 26,
    "date_applied": "2026-02-07",
    "company": "Ironclad Platforms",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Referral",
    "applied_through": "Lever",
    "resume_version": "SPO",
    "rating": 72,
    "status": "Not Selected",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 27,
    "date_applied": "2026-01-30",
    "company": "Luminary SaaS",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Agile Delivery Lead",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Jobgether",
    "applied_through": "Ashby",
    "resume_version": "TPM",
    "rating": 70,
    "status": "No Answer",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "120-145",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 28,
    "date_applied": "2026-03-20",
    "company": "Cascade Technologies",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Sr. Product Owner",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "BuiltIn",
    "applied_through": "SmartRecruiters",
    "resume_version": "SPO",
    "rating": 58,
    "status": "No Answer",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "110-130",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": null
  },
  {
    "id": 29,
    "date_applied": "2026-02-21",
    "company": "Summit Data Co",
    "via_recruiting_firm": 0,
    "recruiting_firm": null,
    "job_title": "Technical Delivery Manager",
    "location_type": "Remote",
    "hybrid_location": null,
    "days_onsite": null,
    "source": "Dice",
    "applied_through": "Workday",
    "resume_version": "TPM",
    "rating": 74,
    "status": "Withdrawn",
    "job_id": null,
    "job_link": null,
    "dashboard_link": null,
    "salary_requested": "130",
    "salary_listed": "140-165",
    "salary_type": "Yearly",
    "contacts": null,
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": null,
    "job_description": null,
    "timeline_id": 9
  }
];
const SEED_TIMELINES = [
  {
    "id": 1,
    "date_recruiter": "2026-05-12",
    "recruiter_name": "Sarah Chen",
    "date_screening": "2026-05-16",
    "screener_name": "Jordan Lee",
    "screening_type": "Zoom",
    "pending": 1,
    "date_closed": null,
    "application_id": 1
  },
  {
    "id": 2,
    "date_recruiter": "2026-05-16",
    "recruiter_name": "Sarah Chen",
    "date_screening": "2026-05-20",
    "screener_name": "Jordan Lee",
    "screening_type": "Zoom",
    "pending": 1,
    "date_closed": null,
    "application_id": 2
  },
  {
    "id": 3,
    "date_recruiter": "2026-05-06",
    "recruiter_name": "Sarah Chen",
    "date_screening": "2026-05-10",
    "screener_name": "Jordan Lee",
    "screening_type": "MS Teams",
    "pending": 1,
    "date_closed": null,
    "application_id": 3
  },
  {
    "id": 4,
    "date_recruiter": "2026-04-20",
    "recruiter_name": "Rachel Moore",
    "date_screening": "2026-04-24",
    "screener_name": "Rachel Moore",
    "screening_type": "Phone",
    "offer_date": "2026-05-22",
    "offer_notes": "Base 138k, 10% bonus, 4 weeks PTO. Deadline June 20.",
    "pending": 1,
    "date_closed": null,
    "application_id": 4
  },
  {
    "id": 5,
    "date_recruiter": "2026-03-10",
    "recruiter_name": "Chris Park",
    "date_screening": "2026-03-16",
    "screener_name": null,
    "screening_type": "Zoom",
    "pending": 0,
    "date_closed": "2026-03-24",
    "application_id": 20
  },
  {
    "id": 6,
    "date_recruiter": "2026-02-08",
    "recruiter_name": "Chris Park",
    "date_screening": "2026-02-13",
    "screener_name": null,
    "screening_type": "Zoom",
    "pending": 0,
    "date_closed": "2026-02-20",
    "application_id": 21
  },
  {
    "id": 7,
    "date_recruiter": "2026-04-25",
    "recruiter_name": "Amy Liu",
    "date_screening": "2026-04-30",
    "screener_name": null,
    "screening_type": "Zoom",
    "pending": 0,
    "date_closed": null,
    "application_id": 22
  },
  {
    "id": 8,
    "date_recruiter": "2026-04-04",
    "recruiter_name": "Amy Liu",
    "date_screening": "2026-04-09",
    "screener_name": null,
    "screening_type": "Zoom",
    "pending": 0,
    "date_closed": null,
    "application_id": 23
  },
  {
    "id": 9,
    "date_recruiter": "2026-02-28",
    "recruiter_name": "Ben Foster",
    "date_screening": "2026-03-05",
    "screener_name": null,
    "screening_type": "Zoom",
    "pending": 0,
    "date_closed": "2026-03-10",
    "application_id": 29
  }
];
const SEED_ROUNDS = [
  {
    "id": 1,
    "timeline_id": 1,
    "round_order": 1,
    "interview_date": "2026-05-22",
    "interview_type": "Zoom",
    "interviewer": "VP Engineering, Director PM",
    "notes": "Strong conversation about delivery methodology",
    "is_final_round": 1
  },
  {
    "id": 2,
    "timeline_id": 2,
    "round_order": 1,
    "interview_date": "2026-05-25",
    "interview_type": "MS Teams",
    "interviewer": "VP Engineering, Director PM",
    "notes": "Strong conversation about delivery methodology",
    "is_final_round": 0
  },
  {
    "id": 3,
    "timeline_id": 2,
    "round_order": 2,
    "interview_date": "2026-05-28",
    "interview_type": "Google Meet",
    "interviewer": "VP Engineering, Director PM",
    "notes": "Deep dive on technical background",
    "is_final_round": 0
  },
  {
    "id": 4,
    "timeline_id": 3,
    "round_order": 1,
    "interview_date": "2026-05-15",
    "interview_type": "Zoom",
    "interviewer": "Sr. PM, Engineering Manager",
    "notes": "Strong conversation about delivery methodology",
    "is_final_round": 0
  },
  {
    "id": 5,
    "timeline_id": 3,
    "round_order": 2,
    "interview_date": "2026-05-21",
    "interview_type": "Google Meet",
    "interviewer": "CTO, Product Lead",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "Deep dive on technical background",
    "is_final_round": 0
  },
  {
    "id": 6,
    "timeline_id": 3,
    "round_order": 3,
    "interview_date": "2026-05-27",
    "interview_type": "MS Teams",
    "interviewer": "Sr. PM, Engineering Manager",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "Deep dive on technical background",
    "is_final_round": 1
  },
  {
    "id": 7,
    "timeline_id": 4,
    "round_order": 1,
    "interview_date": "2026-04-30",
    "interview_type": "Zoom",
    "interviewer": "HR Screen",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "",
    "is_final_round": 0
  },
  {
    "id": 8,
    "timeline_id": 4,
    "round_order": 2,
    "interview_date": "2026-05-08",
    "interview_type": "Zoom",
    "interviewer": "VP Product",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "",
    "is_final_round": 0
  },
  {
    "id": 9,
    "timeline_id": 4,
    "round_order": 3,
    "interview_date": "2026-05-16",
    "interview_type": "Zoom",
    "interviewer": "CEO",
    "cover_letter": 0,
    "has_outreach": 1,
    "outreach_notes": "Marcus Webb via LinkedIn",
    "notes": "",
    "is_final_round": 1
  },
  {
    "id": 10,
    "timeline_id": 6,
    "round_order": 1,
    "interview_date": "2026-02-19",
    "interview_type": "Zoom",
    "interviewer": "Hiring Manager",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "",
    "is_final_round": 0
  },
  {
    "id": 11,
    "timeline_id": 8,
    "round_order": 1,
    "interview_date": "2026-04-15",
    "interview_type": "Zoom",
    "interviewer": "Hiring Manager",
    "cover_letter": 0,
    "has_outreach": 0,
    "outreach_notes": null,
    "notes": "",
    "is_final_round": 0
  }
];



// ── Date helper — keeps demo data looking recent ─────────────────────────────
function dAgo(n) {
  const d = new Date();
  d.setDate(d.getDate() - n);
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}-${String(d.getDate()).padStart(2,"0")}`;
}

// ── Rewrite seed dates to relative offsets ────────────────────────────────────
function initSeedDates() {
  [...SEED_APPS, ...SEED_TIMELINES].forEach(obj => {
    ['date_applied','date_recruiter','date_screening','date_closed','offer_date'].forEach(k => {
      if (obj[k]) obj[k] = dAgo(0 + Math.round((new Date("2026-05-29") - new Date(obj[k])) / 86400000));
    });
  });
  SEED_ROUNDS.forEach(r => {
    if (r.interview_date) r.interview_date = dAgo(Math.round((new Date("2026-05-29") - new Date(r.interview_date)) / 86400000));
  });
  // Ensure v2 offer fields exist on all seed timeline entries
  SEED_TIMELINES.forEach(tl => {
    if (!('offer_date'  in tl)) tl.offer_date  = null;
    if (!('offer_notes' in tl)) tl.offer_notes = null;
  });
}
initSeedDates();

// ── Session state (survives refresh, resets on tab close) ────────────────────
const STORAGE_KEY = "cats_demo_state";

function loadState() {
  try {
    const s = sessionStorage.getItem(STORAGE_KEY);
    if (s) return JSON.parse(s);
  } catch(e) {}
  return {
    apps:      JSON.parse(JSON.stringify(SEED_APPS)),
    timelines: JSON.parse(JSON.stringify(SEED_TIMELINES)),
    rounds:    JSON.parse(JSON.stringify(SEED_ROUNDS)),
    auth:      false,
    nextAppId: Math.max(...SEED_APPS.map(a=>a.id)) + 1,
    nextTlId:  Math.max(...SEED_TIMELINES.map(t=>t.id)) + 1,
    nextRndId: Math.max(...SEED_ROUNDS.map(r=>r.id)) + 1,
  };
}
function saveState(s) {
  try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(s)); } catch(e) {}
}

// ── Helper: parse first number from salary string ────────────────────────────
function parseSalaryFloor(val) {
  if (!val) return null;
  const m = val.match(/\d+/);
  return m ? parseInt(m[0]) : null;
}

// ── Mock API — exposed as window.mockApi, called by api() in pipeline-core.php ─
window.mockApi = async function(action, method="GET", body=null, params={}) {
  // Simulate network delay
  await new Promise(r => setTimeout(r, 60));

  const s = loadState();

  // ── Auth ───────────────────────────────────────────────────────────────────
  if (action === "session") {
    return { auth: s.auth, share_token: null };
  }
  if (action === "login") {
    if (body?.username === "demo" && body?.password === "demo") {
      s.auth = true; saveState(s);
      return { auth: true };
    }
    throw new Error("Invalid credentials");
  }
  if (action === "logout") {
    s.auth = false; saveState(s); return null;
  }

  // ── Stats ──────────────────────────────────────────────────────────────────
  if (action === "stats") {
    const apps = s.apps;
    const total = apps.length;
    const active = apps.filter(a=>["Interviewing","Offer"].includes(a.status)).length;
    const reached_recruiter = apps.filter(a=>{
      const tl = s.timelines.find(t=>t.id===a.timeline_id);
      return tl?.date_recruiter;
    }).length;
    const reached_final = apps.filter(a=>{
      const tl = s.timelines.find(t=>t.id===a.timeline_id);
      if (!tl) return false;
      return s.rounds.some(r=>r.timeline_id===tl.id && r.is_final_round);
    }).length;
    const ratings = apps.filter(a=>a.rating).map(a=>a.rating);
    const avg_rating = ratings.length ? Math.round(ratings.reduce((a,b)=>a+b,0)/ratings.length*10)/10 : null;
    const closed_positive = apps.filter(a=>a.status==="Accepted").length;
    const closed_reached = apps.filter(a=>["Rejected","Ghosted"].includes(a.status)).length;
    const closed_no_prog = apps.filter(a=>["Not Selected","No Answer","Withdrawn"].includes(a.status)).length;
    const today = new Date();
    const weekStart = new Date(today); weekStart.setDate(today.getDate()-today.getDay()+1); weekStart.setHours(0,0,0,0);
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const this_week = apps.filter(a=>new Date(a.date_applied)>=weekStart).length;
    const this_month = apps.filter(a=>new Date(a.date_applied)>=monthStart).length;
    const dates = apps.map(a=>new Date(a.date_applied)).filter(Boolean).sort((a,b)=>a-b);
    const firstDate = dates[0];
    const avg_week = firstDate ? Math.round(total / Math.max(1, Math.ceil((today-firstDate)/604800000)) * 10)/10 : null;
    const avg_month = firstDate ? Math.round(total / Math.max(1, Math.ceil((today-firstDate)/2592000000)) * 10)/10 : null;
    return { total, active, reached_recruiter, reached_final_round: reached_final, avg_rating,
             closed_positive, closed_reached, closed_no_prog, this_week, this_month, avg_week, avg_month };
  }

  // ── Applications — list ────────────────────────────────────────────────────
  if (action === "applications") {
    let apps = [...s.apps];

    // Search
    if (params.search) {
      const q = params.search.toLowerCase();
      apps = apps.filter(a=>(a.company||"").toLowerCase().includes(q) ||
        (a.job_title||"").toLowerCase().includes(q) ||
        (a.recruiting_firm||"").toLowerCase().includes(q));
    }
    // Status filter
    if (params.status) {
      const statuses = params.status.split(",");
      apps = apps.filter(a=>statuses.includes(a.status));
    }
    // Resume filter
    if (params.resume) {
      const versions = params.resume.split(",");
      apps = apps.filter(a=>versions.includes(a.resume_version));
    }
    // Applied via filter
    if (params.applied) {
      const vias = params.applied.split(",");
      apps = apps.filter(a=>vias.includes(a.applied_through));
    }
    // Source filter
    if (params.source_filter) {
      const srcs = params.source_filter.split(",");
      apps = apps.filter(a=>srcs.includes(a.source));
    }
    // Rating min
    if (params.rating_min && parseInt(params.rating_min) > 0) {
      apps = apps.filter(a=>a.rating >= parseInt(params.rating_min));
    }
    // Salary filter
    if (params.salary_min && parseInt(params.salary_min) > 0) {
      const smin = parseInt(params.salary_min);
      const stype = params.salary_type_filter || "Yearly";
      apps = apps.filter(a=>{
        if (a.salary_type !== stype) return false;
        const sal = a.salary_listed || a.salary_requested;
        if (!sal) return false;
        const floor = parseSalaryFloor(sal);
        return floor !== null && floor >= smin;
      });
    }
    // Date range
    if (params.date_from) apps = apps.filter(a=>a.date_applied >= params.date_from);
    if (params.date_to)   apps = apps.filter(a=>a.date_applied <= params.date_to);

    // Sort
    const groupOrder = {Interviewing:1,Offer:2,Applied:3,Accepted:4,Rejected:5,Ghosted:6,"Not Selected":7,"No Answer":8,Withdrawn:9};
    const sortFn = (a,b) => {
      const gd = (groupOrder[a.status]||10) - (groupOrder[b.status]||10);
      if (gd !== 0) return gd;
      if (params.sort === "date_asc")    return a.date_applied.localeCompare(b.date_applied);
      if (params.sort === "rating_desc") return (b.rating||0) - (a.rating||0);
      return b.date_applied.localeCompare(a.date_applied);
    };
    apps.sort(sortFn);
    return apps;
  }

  // ── Application — single ───────────────────────────────────────────────────
  if (action === "application") {
    const app = s.apps.find(a=>a.id===parseInt(params.id));
    if (!app) throw new Error("Not found");
    const tl = app.timeline_id ? s.timelines.find(t=>t.id===app.timeline_id) : null;
    const rounds = tl ? s.rounds.filter(r=>r.timeline_id===tl.id).sort((a,b)=>a.round_order-b.round_order) : [];
    // Attach company/position/rating/date_applied to timeline (mirrors JOIN)
    const tlFull = tl ? { ...tl, company: app.company, position: app.job_title,
      rating: app.rating, date_applied: app.date_applied,
      via_recruiting_firm: app.via_recruiting_firm, recruiting_firm: app.recruiting_firm } : null;
    return { application: app, timeline: tlFull, rounds };
  }

  // ── Application — add ──────────────────────────────────────────────────────
  if (action === "application_add") {
    if (!s.auth) throw new Error("Not authenticated");
    const newApp = { ...body, id: s.nextAppId++, user_id: 1, via_recruiting_firm: body.via_recruiting_firm?1:0,
      status: body.status || "Applied", timeline_id: null,
      created_at: new Date().toISOString(), updated_at: new Date().toISOString() };
    s.apps.push(newApp);
    if (newApp.status === "Interviewing") _mockAutoCreateTimeline(s, newApp.id);
    saveState(s);
    return { id: newApp.id };
  }

  // ── Application — update ───────────────────────────────────────────────────
  if (action === "application_update") {
    if (!s.auth) throw new Error("Not authenticated");
    const idx = s.apps.findIndex(a=>a.id===parseInt(params.id));
    if (idx < 0) throw new Error("Not found");
    const prev = s.apps[idx];
    s.apps[idx] = { ...prev, ...body, id: prev.id, via_recruiting_firm: body.via_recruiting_firm?1:0 };
    if (body.status === "Interviewing" && !prev.timeline_id) _mockAutoCreateTimeline(s, prev.id);
    // Save timeline updates if provided
    if (prev.timeline_id && body.date_recruiter !== undefined) {
      const ti = s.timelines.findIndex(t=>t.id===prev.timeline_id);
      if (ti >= 0) Object.assign(s.timelines[ti], {
        date_recruiter: body.date_recruiter||null, recruiter_name: body.recruiter_name||null,
        date_screening: body.date_screening||null, screener_name: body.screener_name||null,
        screening_type: body.screening_type||null, pending: body.pending??1,
        date_closed: body.date_closed||null,
        offer_date: body.offer_date||null, offer_notes: body.offer_notes||null });
    }
    saveState(s);
    return null;
  }

  // ── Application — delete ───────────────────────────────────────────────────
  if (action === "application_delete") {
    if (!s.auth) throw new Error("Not authenticated");
    const app = s.apps.find(a=>a.id===parseInt(params.id));
    if (!app) throw new Error("Not found");
    if (app.timeline_id) {
      s.rounds = s.rounds.filter(r=>r.timeline_id!==app.timeline_id);
      s.timelines = s.timelines.filter(t=>t.id!==app.timeline_id);
    }
    s.apps = s.apps.filter(a=>a.id!==parseInt(params.id));
    saveState(s);
    return null;
  }

  // ── Application — quick status ─────────────────────────────────────────────
  if (action === "application_status") {
    if (!s.auth) throw new Error("Not authenticated");
    const app = s.apps.find(a=>a.id===parseInt(params.id));
    if (!app) throw new Error("Not found");
    app.status = body.status;
    const today = body.today || localToday();
    if (app.timeline_id) {
      const tl = s.timelines.find(t=>t.id===app.timeline_id);
      if (tl) {
        if (body.status === "Ghosted") { tl.pending=0; tl.date_closed=null; }
        else if (["Rejected","Not Selected","No Answer","Withdrawn","Accepted"].includes(body.status)) { tl.pending=0; tl.date_closed=today; }
        else if (["Interviewing","Offer","Applied"].includes(body.status)) { tl.pending=1; tl.date_closed=null; }
      }
    } else if (body.status === "Interviewing") {
      _mockAutoCreateTimeline(s, app.id, today);
    }
    saveState(s);
    return null;
  }

  // ── Timeline — list ────────────────────────────────────────────────────────
  if (action === "timeline") {
    return s.timelines.map(tl => {
      const app = s.apps.find(a=>a.id===tl.application_id);
      const rnds = s.rounds.filter(r=>r.timeline_id===tl.id).sort((a,b)=>a.round_order-b.round_order);
      return { ...tl,
        company: app ? ((app.company||"").trim() || app.recruiting_firm || "") : tl.company||"",
        position: app?.job_title || "",
        rating: app?.rating || null,
        date_applied: app?.date_applied || null,
        status: app?.status || null,
        via_recruiting_firm: app?.via_recruiting_firm||0,
        recruiting_firm: app?.recruiting_firm||null,
        rounds: rnds.map(r=>r.interview_date).filter(Boolean),
        rounds_full: rnds,
      };
    }).sort((a,b)=>(a.date_applied||"").localeCompare(b.date_applied||""));
  }

  // ── Timeline — single ──────────────────────────────────────────────────────
  if (action === "timeline_entry") {
    const tl = s.timelines.find(t=>t.id===parseInt(params.id));
    if (!tl) throw new Error("Not found");
    const app = s.apps.find(a=>a.id===tl.application_id);
    const rnds = s.rounds.filter(r=>r.timeline_id===tl.id).sort((a,b)=>a.round_order-b.round_order);
    return { ...tl, company: app?.company||"", position: app?.job_title||"",
      rating: app?.rating||null, date_applied: app?.date_applied||null,
      status: app?.status||null, rounds: rnds };
  }

  // ── Timeline — update ──────────────────────────────────────────────────────
  if (action === "timeline_update") {
    if (!s.auth) throw new Error("Not authenticated");
    const ti = s.timelines.findIndex(t=>t.id===parseInt(params.id));
    if (ti < 0) throw new Error("Not found");
    Object.assign(s.timelines[ti], {
      date_recruiter: body.date_recruiter||null, recruiter_name: body.recruiter_name||null,
      date_screening: body.date_screening||null, screener_name: body.screener_name||null,
      screening_type: body.screening_type||null, pending: body.pending??1,
      date_closed: body.date_closed||null,
      offer_date: body.offer_date||null, offer_notes: body.offer_notes||null });
    saveState(s);
    return null;
  }

  // ── Timeline — delete ──────────────────────────────────────────────────────
  if (action === "timeline_delete") {
    if (!s.auth) throw new Error("Not authenticated");
    s.rounds = s.rounds.filter(r=>r.timeline_id!==parseInt(params.id));
    s.timelines = s.timelines.filter(t=>t.id!==parseInt(params.id));
    s.apps.forEach(a=>{ if(a.timeline_id===parseInt(params.id)) a.timeline_id=null; });
    saveState(s);
    return null;
  }

  // ── Rounds ─────────────────────────────────────────────────────────────────
  if (action === "rounds") {
    return s.rounds.filter(r=>r.timeline_id===parseInt(params.timeline_id)).sort((a,b)=>a.round_order-b.round_order);
  }

  if (action === "round_save") {
    if (!s.auth) throw new Error("Not authenticated");
    if (body.is_final_round) {
      s.rounds.filter(r=>r.timeline_id===body.timeline_id).forEach(r=>r.is_final_round=0);
    }
    const existing = s.rounds.find(r=>r.timeline_id===body.timeline_id && r.round_order===body.round_order);
    if (existing) {
      Object.assign(existing, { interview_date: body.interview_date||null,
        interview_type: body.interview_type||null, interviewer: body.interviewer||null,
        notes: body.notes||null, is_final_round: body.is_final_round?1:0 });
    } else {
      s.rounds.push({ id: s.nextRndId++, timeline_id: body.timeline_id,
        round_order: body.round_order, interview_date: body.interview_date||null,
        interview_type: body.interview_type||null, interviewer: body.interviewer||null,
        notes: body.notes||null, is_final_round: body.is_final_round?1:0 });
    }
    saveState(s);
    return { id: existing?.id || s.nextRndId-1 };
  }

  if (action === "round_delete") {
    if (!s.auth) throw new Error("Not authenticated");
    s.rounds = s.rounds.filter(r=>r.id!==parseInt(params.id));
    saveState(s);
    return null;
  }

  // ── Export ────────────────────────────────────────────────────────────────
  if (action === "export") {
    let apps = [...s.apps];
    if (params.date_from) apps = apps.filter(a=>a.date_applied >= params.date_from);
    if (params.date_to)   apps = apps.filter(a=>a.date_applied <= params.date_to);
    apps.sort((a,b)=>a.date_applied.localeCompare(b.date_applied));
    return apps.map(a => {
      const tl = a.timeline_id ? s.timelines.find(t=>t.id===a.timeline_id) : null;
      const rounds = tl
        ? s.rounds.filter(r=>r.timeline_id===tl.id).sort((a,b)=>a.round_order-b.round_order)
            .map(r=>({ interview_date:r.interview_date, interview_type:r.interview_type, interviewer:r.interviewer }))
        : [];
      return { ...a,
        date_recruiter: tl?.date_recruiter||null, recruiter_name: tl?.recruiter_name||null,
        date_screening: tl?.date_screening||null, screener_name: tl?.screener_name||null,
        screening_type: tl?.screening_type||null, offer_date: tl?.offer_date||null,
        offer_notes:    tl?.offer_notes||null,    date_closed:  tl?.date_closed||null,
        rounds,
      };
    });
  }

  // ── Share token (no-op in demo — share is disabled in demo mode) ─────────────
  if (action === "generate_share_token") return { token: null };
  if (action === "revoke_share_token")   return null;

  // ── Migration (no-op in demo) ──────────────────────────────────────────────
  if (action === "run_migration") return { workday_links_moved: 0, linkedin_consolidated: 0 };

  throw new Error(`Unknown action: ${action}`);
}

// ── Auto-create timeline helper ────────────────────────────────────────────────
function _mockAutoCreateTimeline(state, appId, today=null) {
  const app = state.apps.find(a=>a.id===appId);
  if (!app) return;
  const d = today || localToday();
  const tl = { id: state.nextTlId++, date_recruiter: d, recruiter_name: null,
    date_screening: null, screener_name: null, screening_type: null,
    pending: 1, date_closed: null, offer_date: null, offer_notes: null,
    application_id: appId };
  state.timelines.push(tl);
  app.timeline_id = tl.id;
}