---
colors:
  surface: '#f8ede1'
  surface-dim: '#d7b595'
  surface-bright: '#fff6ee'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3e1cf'
  surface-container: '#eed3ba'
  surface-container-high: '#e7cbb2'
  surface-container-highest: '#d7b595'
  on-surface: '#151311'
  on-surface-variant: '#2a2420'
  inverse-surface: '#151311'
  inverse-on-surface: '#eed3ba'
  outline: '#6f5d55'
  outline-variant: '#d7b595'
  surface-tint: '#4b262f'
  primary: '#151311'
  on-primary: '#ffffff'
  primary-container: '#241f1b'
  on-primary-container: '#eed3ba'
  inverse-primary: '#eed3ba'
  secondary: '#4b262f'
  on-secondary: '#ffffff'
  secondary-container: '#e8cdd2'
  on-secondary-container: '#351b22'
  tertiary: '#eed3ba'
  on-tertiary: '#151311'
  tertiary-container: '#f7e8d8'
  on-tertiary-container: '#4b262f'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#151311'
  primary-fixed-dim: '#241f1b'
  on-primary-fixed: '#eed3ba'
  on-primary-fixed-variant: '#f7e8d8'
  secondary-fixed: '#4b262f'
  secondary-fixed-dim: '#351b22'
  on-secondary-fixed: '#ffffff'
  on-secondary-fixed-variant: '#e8cdd2'
  tertiary-fixed: '#eed3ba'
  tertiary-fixed-dim: '#e7cbb2'
  on-tertiary-fixed: '#151311'
  on-tertiary-fixed-variant: '#4b262f'
  background: '#f8ede1'
  on-background: '#151311'
  surface-variant: '#f3e1cf'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-lg:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-max: 1440px
  margin-desktop: 32px
  gutter-desktop: 24px
  margin-mobile: 16px
  gutter-mobile: 16px
  stack-sm: 12px
  stack-md: 24px
---

## Brand & Style

The brand personality of the design system is disciplined, premium, warm, and athletic. It moves away from generic green fitness software into a more mature club-management identity: dark ink, deep wine, and almond surfaces. The system should feel controlled, confident, and operational without becoming cold.

The design style is **Premium Athletic Minimalism**. It prioritizes high-quality typography, compact information density, matte surfaces, and strong contrast. The visual language conveys reliability and boutique-gym sophistication, targeting professional gym owners who need repeated-use tools that still feel polished.

## Colors

The palette is built around three anchor colors. **Obsidian Ink** (`#151311`) is the primary color and should be used for key actions, active navigation, dominant text, dark surfaces, and high-contrast interface anchors. **Velvet Curfew** (`#4B262F`) is the secondary color and should support charts, secondary status indicators, deep accents, and premium contrast panels. **Almond Hearth** (`#EED3BA`) is the tertiary color and should support warm surfaces, chips, quiet cards, highlighted containers, and subtle brand accents.

Derived surfaces use lighter Almond variants (`#F8EDE1`, `#F3E1CF`, `#E7CBB2`) to keep the dashboard warm and readable. Neutrals are derived from Obsidian (`#151311`, `#2A2420`, `#6F5D55`) so text and controls stay visually connected to the brand. Avoid neon, glow, and saturated sport colors; colors should remain solid, matte, and tactile. Semantic red and green may still be used only for errors, danger, success, and operational status.

## Typography

The design system utilizes **Inter** exclusively to ensure maximum legibility and a clean, systematic feel across the dashboard. The typographic hierarchy relies on weight and intentional spacing rather than decorative flourishes.

Headlines use tighter letter spacing to appear more "designed" and authoritative, while labels use slightly increased tracking and uppercase styling to provide clear structural markers for data points. For mobile, headline sizes are scaled down to prevent awkward wrapping, ensuring the dashboard remains professional on smaller tablets or handheld devices.

## Layout & Spacing

The layout follows a **Fixed-Fluid Hybrid** model. The main content is contained within a 1440px max-width container, centered on the screen, while the sidebar remains fixed to the left. The internal grid uses a 12-column system for desktop, collapsing to 1 column for mobile.

Spacing is governed by an 8px rhythm. Content blocks (cards) are separated by 24px gutters to allow the UI to "breathe," reflecting the Zen philosophy. On mobile, margins are reduced to 16px to maximize screen real estate, and stack spacing is tightened to keep related information clusters together.

## Elevation & Depth

Hierarchy is established through **Tonal Layering** and **Ambient Shadows**. The base background is the lightest greenish-gray, while interactive cards sit on pure white surfaces.

Depth is communicated through extremely soft, diffused shadows (0px 10px 30px rgba(21,19,17,0.05)). There are no harsh drop shadows. To maintain the minimalist aesthetic, 1px subtle borders in Almond-derived warm neutrals (`#D7B595`) are used to define boundaries where shadows might feel too heavy. This creates a tactile, paper-like quality rather than a digital, floating one.

## Shapes

The shape language uses **Rounded** (Level 2) corners. Elements like primary dashboard cards and input fields use an 8px (0.5rem) radius, while larger containers or the sidebar selection indicators use a 16px (1rem) radius.

This level of roundedness strikes a balance between professional geometry and approachable softness. It avoids the clinical feel of sharp corners while remaining more serious than the "bubbly" appearance of pill-shaped or Level 3 systems.

## Components

### Buttons
Primary buttons use the Obsidian Ink background with white text. They should have a subtle 1px inner highlight on the top edge to give a very slight pressed-metal or high-quality plastic feel. Secondary buttons are outlined with a 1px Almond-derived border.

### Cards
Cards are the primary organizational unit. They may use white or very light Almond surfaces, the standard soft shadow, and 24px internal padding. Titles within cards should be `title-lg` in Obsidian or Velvet depending on hierarchy.

### Data Visualization
Charts should use solid fills or very subtle gradients within the Obsidian and Velvet families. Almond may be used for low-emphasis fills and quiet comparison states. Grid lines in charts should be extremely faint or omitted entirely to reduce visual noise.

### Navigation
The sidebar uses a warm Almond surface (`#F3E1CF`) to contrast against the main content area. Active states are indicated by an Obsidian Ink vertical bar on the left and a soft Almond tint, rather than a full-width high-contrast block.

### Inputs
Input fields use a subtle Almond background fill (`#F3E1CF`) with no border in their default state, gaining an Obsidian Ink 1px border only upon focus. This keeps forms clean and quiet when not in use.
