# ENTERPRISE HRMS — UI COMPONENT DESIGN SYSTEM SPECIFICATION
**Version**: 2.0 (Enterprise SaaS Edition)  
**Stylesheet**: `public/css/enterprise-design-system.css`  
**Blade Components**: `resources/views/components/ui/`  

---

## 1. Design Tokens & Core Palette

```css
:root {
    /* Brand Navy */
    --brand-navy-950: #0B0F19;
    --brand-navy-900: #101828;
    --brand-navy-800: #1D2939;
    --brand-sidebar-bg: #101828;

    /* Primary Accent: Enterprise Indigo */
    --brand-indigo: #4F46E5;
    --brand-indigo-hover: #4338CA;
    --brand-indigo-subtle: #EEF2FF;
    --brand-indigo-ring: rgba(79, 70, 229, 0.16);

    /* Secondary Accent: Operational Teal */
    --brand-teal: #0D9488;
    --brand-teal-hover: #0F766E;
    --brand-teal-subtle: #F0FDF4;

    /* Surfaces */
    --surface-canvas: #F8FAFC;
    --surface-card: #FFFFFF;
    --surface-subtle: #F1F5F9;

    /* Status Colors */
    --status-success: #12B76A;
    --status-warning: #F79009;
    --status-danger: #F04438;
    --status-info: #2E90FA;

    /* Typography */
    --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}
```

---

## 2. Reusable Blade Components

### `<x-ui.dropdown>`
**Path**: `resources/views/components/ui/dropdown.blade.php`  
**Purpose**: Standardized wrapper for buttons triggering dropdown menus.  
**Props**: `id`, `label`, `icon`, `variant`, `size`, `align`, `class`, `menuClass`.

### `<x-ui.action-dropdown>`
**Path**: `resources/views/components/ui/action-dropdown.blade.php`  
**Purpose**: Compact 3-dot vertical action dropdown for table rows.  
**Features**: Automatic viewport boundary clipping prevention (`data-bs-boundary="viewport"`), high z-index.

### `<x-ui.filter-dropdown>`
**Path**: `resources/views/components/ui/filter-dropdown.blade.php`  
**Purpose**: Standardized filter toolbar with search input, status selector, and reset button.

### `<x-ui.profile-modal>`
**Path**: `resources/views/components/ui/profile-modal.blade.php`  
**Purpose**: Global slide-up / centered identity modal displaying personal, employment, and security telemetry with Page 94 RBAC salary masking.

---

## 3. Component Classes & Standards

### Tables (`.table-enterprise`)
- Header: `#F8FAFC` background, `#475467` text, `11.5px` uppercase, `font-weight: 600`.
- Padding: `12px 16px`.
- Transitions: Row hover subtle highlight `#F8FAFC` (`all 0.15s ease`).
- Identifiers: Enclosed in `.code-font` (JetBrains Mono) with badge styling.

### Status Pills (`.status-pill`)
- Pill container with `9999px` border radius, subtle border, and pulsing dot:
  - `.status-pill-success`: `#ECFDF3` bg, `#027A48` text, `#12B76A` dot.
  - `.status-pill-danger`: `#FEF3F2` bg, `#B42318` text, `#F04438` dot.
  - `.status-pill-warning`: `#FFFAEB` bg, `#B54708` text, `#F79009` dot.
  - `.status-pill-info`: `#EFF8FF` bg, `#175CD3` text, `#2E90FA` dot.

### KPI Tiles (`.card-enterprise`)
- Elevation: `box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05)`.
- Radius: `12px`.
- Border: `1px solid #E4E7EC`.
- Metric numbers: `font-size: 24px; font-weight: 700; color: #0F172A;`.

