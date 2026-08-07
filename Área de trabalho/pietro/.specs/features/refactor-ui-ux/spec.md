# Feature Spec: Premium UI/UX Refactor

Refactor the website to reach elite commercial standards, fixing visibility, copywriting, and layout bugs.

## Requirements

### R1: Hero Section Fixes
- **ID-1.1**: Fix hero text contrast. Grey text is too opaque. Use a more readable color or dynamic shadow.
- **ID-1.2**: Refactor hero layout to ensure it doesn't "stick" to the header.
- **ID-1.3**: Update copywriting to focus on "Branding Authority" and "Value Generation" for corporate clients.

### R2: Commercial Copywriting
- **ID-2.1**: Refactor all section titles and descriptions to use persuasive commercial language.
- **ID-2.2**: Use empathy to connect with common personas (marketing managers, HR, owners) while maintaining professional authority.

### R3: Category Grid & Navigation
- **ID-3.1**: Fix broken category grid in the "Explorar Linhas" section.
- **ID-3.2**: Enable navigation: clicking a category card MUST redirect to `catalogo.html` with that category filtered.
- **ID-3.3**: Ensure all 171 categories are accessible, not just featured ones.

### R4: Visual Design & Color Palette
- **ID-4.1**: Create a cohesive color palette based on the premium Zinc/Black/Off-white cards.
- **ID-4.2**: Standardize spacing and remove HTML/CSS duplication.
- **ID-4.3**: Implement micro-interactions (magnetic buttons, smooth hover transitions) using GSAP.

### R5: Functional Reliability
- **ID-5.1**: Ensure 100% of products load correctly in the grid.
- **ID-5.2**: Fix any "undefined" keys in product rendering.
- **ID-5.3**: Optimize image loading performance.
