# Demo photos (optional)

`wp keystone seed` uses real photos from these folders when they exist, and falls back to
locally generated illustrations otherwise. Any size of JPEG, PNG or WebP works: images are
resized to 1600 px max and re-encoded at 82% quality at seed time.

```
exterior/villa/       exterior/apartment/
exterior/house/       exterior/office/
interior/             agents/   (portraits, 4:5 works best)
```

Files are picked in alphabetical order and reused cyclically. Use photos you have the
rights to publish (e.g. your own, or a licence that allows redistribution).
