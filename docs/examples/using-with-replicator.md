---
title: "Example: Using with Replicator"
---

```html
<input class="mb-6" type="text" name="line_items[0][name]" />
<textarea name="line_items[0][description]"></textarea>

<input class="mb-6" type="text" name="line_items[1][name]" />
<textarea name="line_items[1][description]"></textarea>
```

In the example above: `line_items` is the handle of the Replicator field & name/description are the fields.

Basically, you just have to have the name of the inputs like they are in that example so what saves to the entry's markdown file is in the right format for Replicator.
