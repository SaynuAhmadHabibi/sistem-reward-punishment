# CSS Stylesheet Documentation

## Overview

File `style.css` yang telah diperbaharui dengan desain **modern, clean, dan professional**. Menggunakan CSS Variables untuk mudah dikustomisasi dan responsive design untuk semua device.

## Color Palette

### Primary Colors
- **Primary:** `#1e40af` - Main brand color
- **Primary Light:** `#3b82f6` - Hover states
- **Primary Dark:** `#1e3a8a` - Active states

### Status Colors
- **Success:** `#10b981` - Positive actions, rewards
- **Danger:** `#ef4444` - Negative actions, punishments
- **Warning:** `#f59e0b` - Caution, warnings
- **Info:** `#06b6d4` - Information, notices

### Neutral Colors
- **Light:** `#f9fafb` - Backgrounds
- **Light 2:** `#f3f4f6` - Secondary backgrounds
- **Light 3:** `#e5e7eb` - Borders
- **Dark:** `#1f2937` - Text
- **Dark 2:** `#374151` - Secondary text
- **Gray:** `#6b7280` - Muted text

## Components

### Buttons
Available button types:
- `.btn-primary` - Primary action button
- `.btn-success` - Success/confirm button
- `.btn-danger` - Delete/cancel button
- `.btn-warning` - Warning action button
- `.btn-secondary` - Secondary action button

Sizes:
- `.btn-sm` - Small button
- (default) - Normal button
- `.btn-lg` - Large button
- `.btn-block` - Full width button

### Cards
```html
<div class="card">
    <div class="card-header">
        <h2>Card Title</h2>
    </div>
    <div class="card-body">
        Content here
    </div>
    <div class="card-footer">
        Footer content
    </div>
</div>
```

### Tables
Professional table styling with hover effects and responsive design.

```html
<table class="table">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Data 1</td>
            <td>Data 2</td>
        </tr>
    </tbody>
</table>
```

### Badges
```html
<span class="badge badge-primary">Badge</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-info">Info</span>
```

### Alerts
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>
```

### Forms
```html
<div class="form-group">
    <label class="form-label">Label</label>
    <input type="text" class="form-control" placeholder="Placeholder">
</div>
```

### Stat Cards
```html
<div class="stat-card stat-card-reward">
    <div class="stat-number">125</div>
    <div class="stat-label">Rewards</div>
</div>

<div class="stat-card stat-card-punishment">
    <div class="stat-number">18</div>
    <div class="stat-label">Punishments</div>
</div>

<div class="stat-card stat-card-employee">
    <div class="stat-number">245</div>
    <div class="stat-label">Employees</div>
</div>

<div class="stat-card stat-card-topsis">
    <div class="stat-number">8.5</div>
    <div class="stat-label">Avg Score</div>
</div>
```

## Animations

Available animations:
- `fadeIn` - Fade in from transparent
- `slideInLeft` - Slide in from left
- `slideInRight` - Slide in from right
- `slideInUp` - Slide in from bottom
- `pulse` - Pulse animation
- `spin` - Rotation animation

## Responsive Breakpoints

- **Desktop:** 768px+ (full design)
- **Tablet:** 576px - 767px (adjusted spacing)
- **Mobile:** < 576px (optimized layout)

## CSS Variables

All colors, spacing, and sizing use CSS variables for easy customization:

```css
:root {
    --primary: #1e40af;
    --success: #10b981;
    --spacing-md: 1rem;
    --radius-lg: 1rem;
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
```

## Utility Classes

### Spacing
- `mt-1, mt-2, mt-3, mt-4` - Margin top
- `mb-1, mb-2, mb-3, mb-4` - Margin bottom
- `p-1, p-2, p-3, p-4` - Padding

### Display
- `d-flex` - Flexbox container
- `d-grid` - Grid container
- `d-none` - Hide element
- `flex-center` - Center content
- `flex-between` - Space between items
- `flex-col` - Flex column

### Text
- `text-center` - Center align text
- `text-left` - Left align text
- `text-right` - Right align text
- `text-muted` - Muted color
- `text-primary` - Primary color
- `text-success` - Success color
- `text-danger` - Danger color
- `text-warning` - Warning color

### Background
- `bg-light` - Light background
- `bg-light-2` - Light 2 background

### Shadow
- `shadow-sm` - Small shadow
- `shadow` - Medium shadow
- `shadow-lg` - Large shadow

## Best Practices

1. **Use CSS Variables** - Don't hardcode colors, use variables
2. **Responsive First** - Design for mobile first, then enhance
3. **Accessibility** - Ensure sufficient color contrast
4. **Performance** - Minimize repaints and reflows
5. **Consistency** - Use consistent spacing and sizing

## Browser Support

- Chrome/Edge: Latest versions
- Firefox: Latest versions
- Safari: Latest versions
- Mobile browsers: Full support with responsive design

## Customization

To customize colors, edit the CSS variables in `:root`:

```css
:root {
    --primary: #your-color;
    --success: #your-color;
    --danger: #your-color;
}
```

All components using these variables will automatically update.

## Performance Tips

1. Use CSS Variables instead of duplicating values
2. Minimize the use of `box-shadow` on frequently animated elements
3. Use `transform` instead of `top/left` for animations
4. Leverage CSS Grid for complex layouts
5. Use media queries for responsive design

## Print Styles

The stylesheet includes optimized print styles for PDF export:
- Removes interactive elements
- Optimizes colors for printing
- Maintains readability

---

**Last Updated:** 29 January 2026  
**Version:** 1.0.0  
**License:** MIT
