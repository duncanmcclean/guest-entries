---
title: File Uploads
---

Sometimes you may want your users to be able to upload files to your entries. Guest Entries makes this easy for you.

First things first, ensure you have an assets field on the entry blueprint, let's use `attachment` as an example. Make sure the Assets field has an asset container specified.

Then in your Guest Entries form, add `files="true"` as a parameter to the form and create a file input.

```antlers
{{ guest-entries:create collection="articles" files="true" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <input type="file" name="attachments[]">

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

If you need to upload multiple files, just use multiple inputs, like so:

```antlers
{{ guest-entries:create collection="articles" files="true" }}
    <!-- ... -->

    <input type="file" name="attachments[]">
    <input type="file" name="attachments[]">
    <input type="file" name="attachments[]">

    <!-- ... -->
{{ /guest-entries:create }}
```

When the form is submitted, the file will be uploaded to the specified asset container and will be linked to the entry.
