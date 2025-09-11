# Background Images

## How to add the classroom background image:

1. **Save your classroom image** as `classroom-bg.jpg` in this `/images/` folder
2. **Recommended image specifications:**
   - **Format:** JPG or PNG
   - **Size:** 1920x1080 pixels (Full HD) or higher for best quality
   - **File size:** Keep under 500KB for fast loading
   - **Aspect ratio:** 16:9 works best for wide screens

3. **Image placement:** The image will be positioned as:
   - **Background-size:** cover (fills entire screen)
   - **Background-position:** center (centered positioning)
   - **Gradient overlay:** Purple/blue gradient with 85% opacity over the image
   - **Fixed attachment:** Image stays in place when scrolling

## Alternative image names:
If you want to use a different filename, update the CSS in `index.php`:
```css
background-image: 
    linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%),
    url('images/your-image-name.jpg');
```

## Current setup:
- ✅ CSS updated to include background image
- ✅ Images folder created  
- 📋 **Next step:** Add your `classroom-bg.jpg` image file to this folder
