---
title: "Contact Form Block"
slug: "block-contact-form"
audience: "site-owner"
category: "blocks"
order: 16
---

# Contact Form Block

> Contact form for inquiries, feedback, and user communication.

---

## Overview

The Contact Form block adds a customizable contact form to your pages. Submissions are stored in the admin panel and can trigger email notifications.

---

## Fields

| Field | Description |
|-------|-------------|
| **Section Title** | Heading above form |
| **Description** | Optional intro text |
| **Fields** | Name, email, subject, message |
| **Required Fields** | Mark fields as required |
| **Success Message** | Message shown after submission |
| **Redirect After Submission** | Optionally redirect to a published CMS page instead of showing the success message. Leave empty to stay on page. |
| **Email Notification** | Send email on submission |
| **Notification Email** | Recipient email address |

---

## Redirect After Submission

By default, a success message is shown inline after the form is submitted. You can optionally redirect the user to any published CMS page instead — useful for custom "thank you" pages, download pages, or landing page funnels.

1. Open the Contact Form block settings
2. In **Form Settings**, find the **Redirect After Submission** dropdown
3. Select a published page, or leave empty to keep the default inline message

The redirect is handled server-side: the selected page's URL is resolved when the form is submitted and returned in the API response. This works correctly with localized URLs, subdirectory installs, and single-page sites.

---

## Examples

<!-- Examples will be added as blocks below -->

