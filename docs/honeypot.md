---
title: Honeypot
---

Guest Entries includes a simple Honeypot feature to help reduce spam via your front-end forms. Documentation around configuring can be seen under '[Configuration](/configuration)'.

Once you've enabled the Honeypot, ensure to add the field to your forms, like so:

```antlers
{{ guest-entries:create collection="articles" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <input type="hidden" name="zip_code" value=""> <!-- This is my honeypot -->

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```
