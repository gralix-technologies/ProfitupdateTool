# Portfolio Analytics Platform - Complete User Guide

## 📋 Table of Contents
1. [Getting Started](#getting-started)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Step-by-Step Workflow](#step-by-step-workflow)
4. [Customer Management](#customer-management)
5. [Product Management](#product-management)
6. [Formula Engine](#formula-engine)
7. [Dashboard Builder](#dashboard-builder)
8. [All Widget Types](#all-widget-types)
9. [Data Import](#data-import)
10. [Analytics & Reporting](#analytics--reporting)
11. [Admin Functions](#admin-functions)
12. [Best Practices](#best-practices)

---

## 🚀 Getting Started

### Login Credentials

The system comes with three pre-configured user roles:

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | admin@gralix.co | Admin@123! | Full system access |
| **Analyst** | analyst@gralix.co | Analyst@123! | Create & analyze data |
| **Viewer** | viewer@gralix.co | Viewer@123! | View-only access |

### First Login

1. Navigate to `http://localhost:8000`
2. Enter your credentials
3. You'll be redirected to the main dashboard

---

## 👥 User Roles & Permissions

### Admin Role (122 Permissions)
- Full system access
- User management
- System configuration
- Audit trail access
- All product & data operations

### Analyst Role (56 Permissions)
- Create/edit products
- Manage formulas
- Create dashboards
- Import/export data
- View analytics

### Viewer Role (22 Permissions)
- View dashboards
- View reports
- Export data
- View customer 360

---

## 📊 Step-by-Step Workflow

### Complete System Usage Flow

```
1. Customer Management → 2. Product Setup → 3. Formula Creation → 
4. Dashboard Configuration → 5. Data Import → 6. Analysis & Insights
```

---

## 1️⃣ Customer Management

### Creating a Single Customer

**Path:** `Customers` → `Add Customer`

**Required Fields:**
- **Customer ID**: Unique identifier (e.g., CUST001)
- **Customer Name**: Full name
- **Email**: Contact email
- **Phone**: Contact number
- **Branch**: Free text field (e.g., LUSAKA, NDOLA, KITWE)

**Optional Fields:**
- **Date of Birth**: Customer's DOB
- **Gender**: Male/Female/Other
- **Address**: Physical address
- **City**: City of residence
- **Country**: Country
- **Occupation**: Job title/profession
- **Industry**: Industry sector
- **Annual Income**: Yearly income
- **Risk Rating**: Credit risk rating

**Steps:**
1. Click **"Add Customer"** button
2. Fill in all required fields
3. Complete demographic information
4. Click **"Save Customer"**
5. ✅ Customer is created and appears in the list

### Bulk Customer Upload

**Path:** `Customers` → `Bulk Upload`

**Steps:**
1. Click **"Bulk Upload"** button
2. Download the **sample CSV template**
3. Fill in customer data following the template format
4. Upload the completed CSV file
5. Review validation errors (if any)
6. Confirm upload
7. ✅ All customers are imported

**Sample CSV Format:**
```csv
customer_id,name,email,phone,branch,date_of_birth,gender,address,city,country,occupation,industry,annual_income,risk_rating
CUST001,John Doe,john@example.com,+260971234567,LUSAKA,1985-05-15,Male,123 Main St,Lusaka,Zambia,Engineer,Technology,120000,Low
```

### Viewing Customer 360

**Path:** `Customers` → Click on customer name

**What You'll See:**
- **Portfolio Summary**: All products held by customer
- **Risk Analysis**: NPL exposure, risk metrics
- **Profitability**: Revenue, costs, net profitability
- **Demographics**: Personal information
- **Financial Summary**: Total loans, deposits, balances

---

## 2️⃣ Product Management

### Creating a Financial Product

**Path:** `Products` → `New Product`

**Product Categories:**
- Loans (Term Loans, Overdrafts, Working Capital)
- Deposits (Savings, Fixed Deposits, Current Accounts)
- Investments (Bonds, Equities, Mutual Funds)
- Insurance (Life, General, Health)
- Cards (Credit Cards, Debit Cards)

**Steps:**

#### Step 1: Basic Information
1. **Product Name**: e.g., "SME Working Capital Loan"
2. **Category**: Select from dropdown
3. **Description**: Product details
4. **Status**: Active/Inactive

#### Step 2: Define Data Fields

Add fields that this product will track:

**Example for Working Capital Loan:**
- `loan_amount` (Currency)
- `interest_rate` (Percentage)
- `tenor_months` (Integer)
- `disbursement_date` (Date)
- `maturity_date` (Date)
- `outstanding_balance` (Currency)
- `collateral_type` (Lookup: Property, Cash, Equipment)
- `risk_rating` (Lookup: Low, Medium, High)
- `npl_status` (Lookup: Performing, NPL)

**Field Types Available:**
- **Text**: Free text input
- **Number**: Numeric values
- **Currency**: Monetary amounts
- **Percentage**: Percentage values (0-100)
- **Date**: Date picker
- **Lookup**: Predefined options (dropdown)

#### Step 3: Configure Settings
- **Portfolio Value Field**: Select which field represents the main value (e.g., `outstanding_balance`)
- **Customer ID Mandatory**: ✅ Always checked (for profitability tracking)
- **Allow Negative Values**: For overdrafts/credit facilities

#### Step 4: Save Product
Click **"Create Product"** → ✅ Product is ready for use

### Copy Existing Product

**Use Case:** Creating similar products (e.g., SME Loan → Corporate Loan)

**Steps:**
1. Go to `Products` page
2. Find the product to copy
3. Click **"Copy"** button
4. Modify name and fields as needed
5. Save as new product

---

## 3️⃣ Formula Engine

### Understanding Formulas

Formulas calculate metrics from your product data automatically.

**Formula Components:**
- **Fields**: Data from product records (e.g., `loan_amount`, `interest_rate`)
- **Functions**: SUM, AVG, COUNT, MIN, MAX, RATIO, PERCENTAGE
- **Operators**: +, -, *, /, ( )
- **Conditions**: IF, CASE statements

### Creating Formulas

**Path:** `Formulas` → `New Formula`

**Steps:**

#### Example 1: Total Outstanding Portfolio
```
Name: Total Outstanding Loans
Product: SME Working Capital Loan
Expression: SUM(outstanding_balance)
Return Type: Currency
```

#### Example 2: Average Interest Rate
```
Name: Average Interest Rate
Product: SME Working Capital Loan
Expression: AVG(interest_rate)
Return Type: Percentage
```

#### Example 3: NPL Ratio
```
Name: NPL Ratio
Product: SME Working Capital Loan
Expression: RATIO(SUM(IF(npl_status = 'NPL', outstanding_balance, 0)), SUM(outstanding_balance)) * 100
Return Type: Percentage
```

#### Example 4: Portfolio Growth Rate
```
Name: Month-on-Month Growth
Product: SME Working Capital Loan
Expression: GROWTH_RATE(SUM(outstanding_balance))
Return Type: Percentage
```

### Available Functions

| Function | Description | Example |
|----------|-------------|---------|
| **SUM(field)** | Total of all values | `SUM(loan_amount)` |
| **AVG(field)** | Average value | `AVG(interest_rate)` |
| **COUNT(field)** | Count of records | `COUNT(customer_id)` |
| **MIN(field)** | Minimum value | `MIN(interest_rate)` |
| **MAX(field)** | Maximum value | `MAX(loan_amount)` |
| **RATIO(a, b)** | Ratio of a to b | `RATIO(SUM(npls), SUM(total))` |
| **PERCENTAGE(part, whole)** | Percentage | `PERCENTAGE(SUM(npls), SUM(total))` |
| **MOVING_AVG(field, periods)** | Moving average | `MOVING_AVG(balance, 3)` |
| **GROWTH_RATE(field)** | Growth rate | `GROWTH_RATE(portfolio)` |
| **IF(condition, true, false)** | Conditional | `IF(status = 'NPL', 1, 0)` |
| **CASE(field, {val1: res1})** | Multiple conditions | `CASE(rating, {'Low': 0.02, 'High': 0.15})` |

### Testing Formulas

**Before saving:**
1. Click **"Test Formula"** button
2. View calculated result
3. Check for errors
4. Adjust expression if needed
5. Save when correct

---

## 4️⃣ Dashboard Builder

### Creating a Custom Dashboard

**Path:** `Dashboards` → `New Dashboard`

**Steps:**

#### Step 1: Dashboard Information
1. **Dashboard Name**: e.g., "SME Loan Portfolio Dashboard"
2. **Description**: Brief description
3. **Visibility**: Private or Shared
4. **Layout**: Grid layout (4 columns default)

#### Step 2: Add Widgets

Click **"Add Widget"** → Choose widget type:

---

## 5️⃣ All Widget Types

### 1. KPI Widget 📊

**Best For:** Single key metrics

**Configuration:**
- **Title**: e.g., "Total Portfolio Value"
- **Data Source**: Formula
- **Select Formula**: Choose from available formulas
- **Formatting**:
  - Prefix: `ZMW` or `$`
  - Suffix: `M` (millions)
  - Precision: 2 decimal places
  - Format: Percentage (%) or Currency

**Example KPI Widgets:**
- Total Outstanding Loans: `ZMW 125.5M`
- NPL Ratio: `3.2%`
- Average Interest Rate: `15.5%`
- Customer Count: `1,234 customers`

**Visual Display:**
```
┌─────────────────────────┐
│ Total Portfolio         │
│                         │
│    ZMW 125.5M          │
│    ↑ 12.3%             │
└─────────────────────────┘
```

---

### 2. Table Widget 📋

**Best For:** Detailed data listings

**Configuration:**
- **Title**: e.g., "Top 10 Customers by Portfolio"
- **Data Source**: Direct Data Query
- **Product**: Select product
- **Columns to Display**: Select fields
- **Sorting**: By value (descending)
- **Limit**: Number of rows (e.g., 10)

**Example:**
```
┌──────────────────────────────────────────────────┐
│ Top Customers by Outstanding Balance            │
├────────────┬──────────────┬────────────┬────────┤
│ Customer   │ Branch       │ Balance    │ Status │
├────────────┼──────────────┼────────────┼────────┤
│ CUST001    │ LUSAKA       │ 5,000,000  │ Active │
│ CUST045    │ NDOLA        │ 3,200,000  │ Active │
│ CUST023    │ KITWE        │ 2,800,000  │ NPL    │
└────────────┴──────────────┴────────────┴────────┘
```

**Use Cases:**
- Top performing customers
- NPL listings
- Recent transactions
- Branch performance

---

### 3. Pie Chart Widget 🥧

**Best For:** Showing proportions and distribution

**Configuration:**
- **Title**: e.g., "Portfolio Distribution by Branch"
- **Data Source**: Aggregated Data
- **Product**: Select product
- **Group By**: Branch, Risk Rating, Product Type
- **Value Field**: outstanding_balance
- **Aggregation**: SUM
- **Color Scheme**: Default, Custom, Gralix colors

**Example:**
```
      Portfolio by Branch
    ┌─────────────────┐
    │    LUSAKA 45%   │
    │   ╱───────╲     │
    │  │ NDOLA   │    │
    │  │  30%    │    │
    │   ╲───────╱     │
    │   KITWE 25%     │
    └─────────────────┘
```

**Use Cases:**
- Branch distribution
- Product mix
- Risk category breakdown
- Gender distribution
- Industry concentration

---

### 4. Bar Chart Widget 📊

**Best For:** Comparing values across categories

**Configuration:**
- **Title**: e.g., "Loan Portfolio by Risk Rating"
- **Data Source**: Aggregated Data
- **Product**: Select product
- **X-Axis**: Category field (e.g., risk_rating)
- **Y-Axis**: Value field (e.g., outstanding_balance)
- **Aggregation**: SUM, AVG, COUNT
- **Orientation**: Vertical or Horizontal
- **Color Scheme**: Single color or gradient

**Example:**
```
Portfolio by Risk Rating

ZMW (M)
60 ┤     ████
50 ┤     ████  ████
40 ┤     ████  ████
30 ┤████ ████  ████
20 ┤████ ████  ████  ████
10 ┤████ ████  ████  ████
 0 ┼────────────────────────
    Low  Med   High  Def
```

**Use Cases:**
- Branch comparison
- Monthly trends
- Product performance
- Risk distribution
- Customer segments

---

### 5. Line Chart Widget 📈

**Best For:** Trends over time

**Configuration:**
- **Title**: e.g., "Portfolio Growth Trend"
- **Data Source**: Time Series Data
- **Product**: Select product
- **X-Axis**: Date field (e.g., disbursement_date)
- **Y-Axis**: Value field (e.g., loan_amount)
- **Aggregation**: SUM, AVG, COUNT
- **Time Period**: Monthly, Quarterly, Yearly
- **Multiple Lines**: Compare different segments

**Example:**
```
Monthly Portfolio Trend

ZMW (M)
150 ┤              ╭──●
140 ┤           ╭──╯
130 ┤        ╭──╯
120 ┤     ╭──╯
110 ┤  ╭──╯
100 ┤●─╯
    ┼──────────────────────
    Jan Feb Mar Apr May Jun
```

**Use Cases:**
- Portfolio growth over time
- NPL trend analysis
- Monthly disbursements
- Interest rate movements
- Customer acquisition trend

---

### 6. Heatmap Widget 🔥

**Best For:** Multi-dimensional analysis

**Configuration:**
- **Title**: e.g., "Risk Matrix: Branch vs Product Type"
- **Data Source**: Aggregated Data
- **Product**: Select product
- **X-Axis**: First dimension (e.g., branch)
- **Y-Axis**: Second dimension (e.g., risk_rating)
- **Value Field**: Metric to display (e.g., outstanding_balance)
- **Aggregation**: SUM, AVG, COUNT
- **Color Scheme**: 
  - Red-Yellow-Green (risk levels)
  - Blue gradient (intensity)
  - Custom colors

**Example:**
```
NPL Concentration: Branch vs Risk Rating

          LUSAKA  NDOLA   KITWE
    ┌──────────────────────────┐
Low │  🟢      🟢      🟢      │ Low NPL
Med │  🟡      🟢      🟡      │ Medium NPL
High│  🔴      🟡      🔴      │ High NPL
    └──────────────────────────┘
```

**Color Legend:**
- 🟢 Green: Low concentration (0-2%)
- 🟡 Yellow: Medium (2-5%)
- 🔴 Red: High (>5%)

**Use Cases:**
- Risk concentration analysis
- Branch performance matrix
- Product-customer segment analysis
- NPL hotspot identification
- Profitability matrix

---

## 6️⃣ Data Import

### Importing Product Data

**Path:** `Data Import` → Select Product → `Upload Data`

**Steps:**

#### Step 1: Download Sample File
1. Click **"Download Sample CSV"**
2. Sample includes all product-specific fields
3. Column headers match field names exactly

#### Step 2: Prepare Your Data

**Example for Working Capital Loan:**
```csv
customer_id,loan_amount,interest_rate,tenor_months,disbursement_date,outstanding_balance,collateral_type,risk_rating,npl_status
CUST001,1000000,15.5,12,2024-01-15,850000,Property,Low,Performing
CUST002,500000,16.0,24,2024-01-20,480000,Cash,Medium,Performing
CUST003,750000,18.5,18,2023-12-10,120000,Equipment,High,NPL
```

**Data Validation Rules:**
- ✅ Customer ID must exist in system
- ✅ Currency fields: numbers only
- ✅ Percentages: 0-100 range
- ✅ Dates: YYYY-MM-DD format
- ✅ Lookups: Must match predefined options
- ✅ Required fields: Cannot be empty

#### Step 3: Upload File
1. Click **"Choose File"**
2. Select your prepared CSV
3. Click **"Upload"**
4. System validates data
5. Review validation report

#### Step 4: Handle Errors
If errors are found:
- Download error report
- Fix issues in your CSV
- Re-upload corrected file

#### Step 5: Confirm Import
- Review summary statistics
- Click **"Confirm Import"**
- ✅ Data is imported and ready for analysis

### Import Options

**Overwrite Mode:**
- ✅ **Append**: Add new records (default)
- ⚠️ **Replace**: Delete existing data first
- 🔄 **Update**: Update matching records

---

## 7️⃣ Analytics & Reporting

### Main Dashboard

**Path:** `Dashboard` (Home)

**Key Metrics Displayed:**
- Total Portfolio Value
- Growth Rate
- NPL Ratio
- Customer Count
- Revenue Summary

**Interactive Charts:**
- Portfolio Performance by Product
- Branch Distribution
- Risk Analysis
- Trend Analysis

### Product-Specific Dashboards

**Path:** `Dashboards` → Select Dashboard

**Available Pre-built Dashboards:**
1. **Executive Dashboard**: High-level KPIs
2. **Working Capital Loan Dashboard**: Detailed loan analytics
3. **Deposit Dashboard**: Savings & deposits analysis
4. **Risk Dashboard**: NPL and risk metrics
5. **Profitability Dashboard**: Revenue & cost analysis

### Customer 360 View

**Path:** `Customers` → Click customer name

**Tabs Available:**

#### Portfolio Tab
- All products held by customer
- Product balances
- Status of each product

#### Risk Analysis Tab
- NPL exposure
- Risk ratings across products
- Collateral coverage
- Risk trends

#### Insights Tab
- Profitability analysis
- Revenue contribution
- Cross-sell opportunities
- Relationship strength

#### Profitability Tab
- Revenue breakdown
- Cost allocation
- Net profitability
- Profitability margin
- Interest metrics

### Exporting Reports

**Available Formats:**
- 📄 **PDF**: Professional reports
- 📊 **Excel**: Data analysis
- 📋 **CSV**: Raw data

**Steps:**
1. Navigate to desired view
2. Click **"Export"** button
3. Select format
4. Choose filters (optional)
5. Download file

---

## 8️⃣ Admin Functions

**Path:** Click user menu → `Admin` section

### User Management

**Path:** `Admin` → `User Management`

**Functions:**
- ✅ Create new users
- ✅ Assign roles
- ✅ Activate/Deactivate users
- ✅ Reset passwords
- ✅ View user activity

**Creating a User:**
1. Click **"Add User"**
2. Enter name and email
3. Set password
4. Assign role (Admin/Analyst/Viewer)
5. Click **"Create User"**

### Role Management

**Path:** `Admin` → `Role Management`

**Functions:**
- View all roles and permissions
- Create custom roles
- Modify permission sets
- View users per role

### System Settings

**Path:** `Admin` → `System Settings`

**Configurable Settings:**

#### Application Settings
- Application Name
- Application URL

#### Mail Configuration
- Mail Host
- Mail Port

#### Security Settings
- Session Timeout (minutes)
- Max Login Attempts
- Minimum Password Length

#### Backup & Maintenance
- Backup Frequency (Daily/Weekly/Monthly)

**Saving Settings:**
1. Modify desired settings
2. Click **"Save Settings"**
3. ✅ Settings applied immediately

### Audit Trail

**Path:** `Admin` → `Audit Trail`

**What's Tracked:**
- ✅ All data changes (Create/Update/Delete)
- ✅ User login/logout
- ✅ Configuration changes
- ✅ Data imports
- ✅ Export operations

**Filtering Options:**
- By user
- By event type (created/updated/deleted)
- By date range
- By model (Customer/Product/Formula)

**Export Audit Logs:**
1. Apply desired filters
2. Click **"Export"**
3. Download CSV report

### System Logs

**Path:** `Admin` → `System Logs`

**View Recent Activity:**
- Last 100 audit entries
- User actions
- System events
- Data changes

---

## 9️⃣ Best Practices

### Customer Management
✅ **DO:**
- Use consistent Customer ID format (e.g., CUST0001)
- Validate email and phone numbers
- Keep demographic data updated
- Use standardized branch names

❌ **DON'T:**
- Create duplicate customers
- Leave required fields empty
- Use special characters in IDs

### Product Configuration
✅ **DO:**
- Define clear, descriptive field names
- Use appropriate field types
- Set portfolio value field correctly
- Add field descriptions

❌ **DON'T:**
- Mix currency with text fields
- Create overly complex field structures
- Forget to activate products

### Formula Creation
✅ **DO:**
- Test formulas before saving
- Use descriptive formula names
- Comment complex logic
- Validate results against manual calculations

❌ **DON'T:**
- Create circular references
- Use undefined fields
- Ignore division by zero errors
- Skip testing

### Dashboard Design
✅ **DO:**
- Group related metrics
- Use appropriate widget types
- Limit widgets per dashboard (8-12 optimal)
- Use consistent color schemes
- Add descriptive titles

❌ **DON'T:**
- Overcrowd dashboards
- Mix unrelated metrics
- Use too many colors
- Forget to test responsiveness

### Data Import
✅ **DO:**
- Always download sample CSV first
- Validate data before upload
- Use consistent date formats
- Review error reports
- Backup before large imports

❌ **DON'T:**
- Skip validation
- Ignore error messages
- Import without testing
- Overwrite without backup

---

## 🎯 Complete Workflow Example

### Scenario: Setting Up SME Loan Portfolio Tracking

#### Step 1: Create Customers (15 minutes)
```
1. Download customer CSV template
2. Add 50 SME customers with demographics
3. Bulk upload CSV file
4. Verify all customers imported successfully
```

#### Step 2: Create Product (10 minutes)
```
Product Name: SME Working Capital Loan
Category: Loans
Fields:
  - loan_amount (Currency) ✓
  - interest_rate (Percentage) ✓
  - tenor_months (Number) ✓
  - disbursement_date (Date) ✓
  - outstanding_balance (Currency) ✓
  - collateral_type (Lookup: Property, Cash, Equipment) ✓
  - risk_rating (Lookup: Low, Medium, High) ✓
  - npl_status (Lookup: Performing, NPL) ✓
Portfolio Value Field: outstanding_balance
```

#### Step 3: Create Formulas (20 minutes)
```
Formula 1: Total Outstanding Portfolio
  = SUM(outstanding_balance)

Formula 2: NPL Ratio
  = RATIO(SUM(IF(npl_status = 'NPL', outstanding_balance, 0)), SUM(outstanding_balance)) * 100

Formula 3: Average Interest Rate
  = AVG(interest_rate)

Formula 4: High Risk Exposure
  = SUM(IF(risk_rating = 'High', outstanding_balance, 0))

Formula 5: Customer Count
  = COUNT(customer_id)
```

#### Step 4: Build Dashboard (25 minutes)
```
Dashboard: SME Loan Portfolio Overview

Widgets:
1. KPI: Total Portfolio (ZMW 125.5M)
2. KPI: NPL Ratio (3.2%)
3. KPI: Customer Count (50)
4. Pie Chart: Portfolio by Branch
5. Bar Chart: Portfolio by Risk Rating
6. Line Chart: Monthly Growth Trend
7. Heatmap: Risk Matrix (Branch vs Rating)
8. Table: Top 10 Customers by Balance
```

#### Step 5: Import Data (10 minutes)
```
1. Download sample CSV for SME Working Capital Loan
2. Fill in 50 loan records
3. Upload CSV file
4. Fix any validation errors
5. Confirm import
```

#### Step 6: Analyze Results (Ongoing)
```
1. View main dashboard
2. Check NPL trends
3. Identify high-risk concentrations
4. Review profitability by customer
5. Export monthly reports
```

**Total Setup Time:** ~80 minutes
**Result:** Fully functional portfolio analytics system

---

## 📞 Support & Help

### Getting Help
- **User Menu** → `Help` → View documentation
- **Tooltips**: Hover over (i) icons for field explanations
- **Validation Messages**: Read error messages for guidance

### Common Issues

**Issue: Dashboard widgets not displaying**
- ✅ Check if formulas are valid
- ✅ Ensure data exists for the product
- ✅ Verify date ranges in filters

**Issue: Import fails with validation errors**
- ✅ Check CSV format matches sample
- ✅ Verify customer IDs exist
- ✅ Validate date formats (YYYY-MM-DD)
- ✅ Check lookup values match options

**Issue: Formulas return zero**
- ✅ Test formula with sample data
- ✅ Check field names are correct
- ✅ Verify data exists in product

**Issue: Can't see admin menus**
- ✅ Check you're logged in as Admin
- ✅ Clear browser cache and reload
- ✅ Log out and log back in

---

## 🎓 Training Checklist

### For Administrators
- [ ] Create users and assign roles
- [ ] Configure system settings
- [ ] Review audit trail
- [ ] Export system logs
- [ ] Manage permissions

### For Analysts
- [ ] Create customer records
- [ ] Set up new products
- [ ] Build formulas
- [ ] Design dashboards
- [ ] Import data
- [ ] Generate reports

### For Viewers
- [ ] Navigate main dashboard
- [ ] View customer 360
- [ ] Filter and search data
- [ ] Export reports
- [ ] Read insights

---

## 📊 All Available Widget Types Summary

| Widget Type | Icon | Best For | Key Features |
|-------------|------|----------|--------------|
| **KPI** | 📊 | Single metrics | Large number display, trend indicator, customizable format |
| **Table** | 📋 | Detailed listings | Sortable columns, pagination, filters, export |
| **Pie Chart** | 🥧 | Proportions | Percentage distribution, color-coded, interactive |
| **Bar Chart** | 📊 | Comparisons | Vertical/horizontal, grouped/stacked, color schemes |
| **Line Chart** | 📈 | Time trends | Multi-series, area fill, markers, tooltips |
| **Heatmap** | 🔥 | 2D analysis | Color intensity, grid layout, tooltips, drill-down |

---

## 🔒 Security Notes

- 🔐 **Passwords**: Minimum 8 characters, mix of uppercase, lowercase, numbers
- ⏱️ **Session Timeout**: 120 minutes default
- 🔒 **Data Encryption**: Sensitive fields encrypted at rest
- 📝 **Audit Logging**: All actions tracked
- 🚫 **Access Control**: Role-based permissions enforced

---

## 🎉 Quick Tips

💡 **Keyboard Shortcuts:**
- `Ctrl + S`: Save form
- `Ctrl + E`: Export current view
- `Esc`: Close modals

💡 **Performance Tips:**
- Limit dashboard widgets to 8-12
- Use date range filters for large datasets
- Export large reports in CSV format
- Schedule regular data imports

💡 **Data Quality:**
- Run monthly data validation
- Review NPL classifications
- Update customer demographics
- Verify collateral values

---

**Version:** 1.0  
**Last Updated:** 2025-10-16  
**Maintained By:** Gralix Systems Team

---


