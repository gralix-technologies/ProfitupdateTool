# Complete User Demo Guide - Portfolio Analytics Platform

## Overview
This guide provides a comprehensive step-by-step process to create a complete financial product demo with all widget types and formula combinations, similar to the Working Capital Loan setup.

---

## Phase 1: System Setup & Authentication

### Step 1: Access the Platform
1. Navigate to `http://127.0.0.1:8000/login`
2. Login with credentials:
   - **Email**: `analyst@gralix.co`
   - **Password**: `password`
3. Verify you're logged in as "Business Analyst"

### Step 2: Verify System Status
- Confirm dashboard loads with existing data
- Check navigation menu (Dashboard, Products, Customers, Dashboards, Formulas, Data Ingestion)
- Verify all sections are accessible

---

## Phase 2: Create Comprehensive Product Specification

### Step 3: Create New Product
1. Navigate to **Products** → **Create Product**
2. Fill in product details:
   ```
   Product Name: "Corporate Term Loans"
   Category: "Loan"
   Description: "Long-term corporate loans for business expansion, equipment purchase, and capital investment. Features comprehensive risk assessment, IFRS 9 compliance, and detailed portfolio analytics."
   Status: Active ✓
   ```

### Step 4: Define Comprehensive Field Structure (25+ Fields)
Click **Add Field** and create the following fields:

#### Core Identification Fields
1. **customer_id** (Text) - Customer ID
2. **loan_account_number** (Text) - Loan Account Number
3. **loan_reference** (Text) - Loan Reference Number
4. **branch_code** (Text) - Branch Code
5. **officer_id** (Text) - Relationship Officer ID

#### Financial Fields
6. **principal_amount** (Numeric) - Principal Amount
7. **outstanding_balance** (Numeric) - Outstanding Balance ⭐ (Set as Portfolio Value Field)
8. **disbursed_amount** (Numeric) - Disbursed Amount
9. **interest_rate** (Numeric) - Interest Rate
10. **interest_earned** (Numeric) - Interest Earned
11. **penalty_amount** (Numeric) - Penalty Amount
12. **loan_loss_provision** (Numeric) - Loan Loss Provision

#### Risk & Assessment Fields
13. **risk_rating** (Text) - Risk Rating (Low/Medium/High)
14. **credit_score** (Numeric) - Credit Score
15. **probability_default** (Numeric) - Probability of Default
16. **loss_given_default** (Numeric) - Loss Given Default
17. **expected_credit_loss** (Numeric) - Expected Credit Loss
18. **stage** (Text) - IFRS 9 Stage (1/2/3)

#### Business Classification Fields
19. **sector** (Text) - Business Sector
20. **industry** (Text) - Industry Classification
21. **purpose** (Text) - Loan Purpose
22. **collateral_value** (Numeric) - Collateral Value
23. **guarantee_type** (Text) - Guarantee Type

#### Status & Date Fields
24. **status** (Text) - Account Status (Active/NPL/Closed)
25. **disbursement_date** (Date) - Disbursement Date
26. **maturity_date** (Date) - Maturity Date
27. **last_payment_date** (Date) - Last Payment Date
28. **days_past_due** (Numeric) - Days Past Due

#### Additional Analytics Fields
29. **customer_segment** (Text) - Customer Segment
30. **loan_tenor** (Numeric) - Loan Tenor (Months)
31. **payment_frequency** (Text) - Payment Frequency

### Step 5: Set Portfolio Value Field
- Select **outstanding_balance** as the Portfolio Value Field
- Click **Create Product**

---

## Phase 3: Create Comprehensive Formula Library

### Step 6: Create Portfolio Metrics Formulas
Navigate to **Formulas** → **Create Formula** and create:

#### Basic Portfolio Metrics
1. **Total Portfolio Value**
   ```
   Name: Total Portfolio Value
   Expression: SUM(outstanding_balance)
   Product: Corporate Term Loans
   Description: Total value of all corporate term loans
   ```

2. **Active Portfolio Value**
   ```
   Name: Active Portfolio Value
   Expression: SUM(outstanding_balance WHERE status = "active")
   Product: Corporate Term Loans
   Description: Value of active loans only
   ```

3. **Loan Count**
   ```
   Name: Loan Count
   Expression: COUNT(*)
   Product: Corporate Term Loans
   Description: Total number of corporate term loans
   ```

4. **Average Loan Size**
   ```
   Name: Average Loan Size
   Expression: AVG(outstanding_balance)
   Product: Corporate Term Loans
   Description: Average size per loan
   ```

#### Risk Metrics Formulas
5. **NPL Ratio**
   ```
   Name: NPL Ratio
   Expression: SUM(outstanding_balance WHERE status = "npl") / SUM(outstanding_balance) * 100
   Product: Corporate Term Loans
   Description: Non-performing loans as percentage of total
   ```

6. **NPL Amount**
   ```
   Name: NPL Amount
   Expression: SUM(outstanding_balance WHERE status = "npl")
   Product: Corporate Term Loans
   Description: Total amount in non-performing loans
   ```

7. **High Risk Loans Count**
   ```
   Name: High Risk Loans Count
   Expression: COUNT(*) WHERE risk_rating = "High"
   Product: Corporate Term Loans
   Description: Number of high-risk loans
   ```

#### IFRS 9 Compliance Formulas
8. **Stage 1 ECL (12-month)**
   ```
   Name: Stage 1 ECL (12-month)
   Expression: SUM(expected_credit_loss WHERE stage = "1")
   Product: Corporate Term Loans
   Description: Expected Credit Loss for Stage 1 loans
   ```

9. **Stage 2 ECL (Lifetime)**
   ```
   Name: Stage 2 ECL (Lifetime)
   Expression: SUM(expected_credit_loss WHERE stage = "2")
   Product: Corporate Term Loans
   Description: Expected Credit Loss for Stage 2 loans
   ```

10. **Stage 3 ECL (Credit Impaired)**
    ```
    Name: Stage 3 ECL (Credit Impaired)
    Expression: SUM(expected_credit_loss WHERE stage = "3")
    Product: Corporate Term Loans
    Description: Expected Credit Loss for Stage 3 loans
    ```

#### Sector Analysis Formulas
11. **Manufacturing Sector Value**
    ```
    Name: Manufacturing Sector Value
    Expression: SUM(outstanding_balance WHERE sector = "Manufacturing")
    Product: Corporate Term Loans
    Description: Total value in Manufacturing sector
    ```

12. **Services Sector Value**
    ```
    Name: Services Sector Value
    Expression: SUM(outstanding_balance WHERE sector = "Services")
    Product: Corporate Term Loans
    Description: Total value in Services sector
    ```

#### Advanced Analytics Formulas
13. **Capital Adequacy Ratio**
    ```
    Name: Capital Adequacy Ratio
    Expression: (SUM(collateral_value) / SUM(outstanding_balance)) * 100
    Product: Corporate Term Loans
    Description: Capital adequacy ratio calculation
    ```

14. **Loan Loss Provision Coverage**
    ```
    Name: Loan Loss Provision Coverage
    Expression: (SUM(loan_loss_provision) / SUM(outstanding_balance WHERE status = "npl")) * 100
    Product: Corporate Term Loans
    Description: LLP coverage ratio
    ```

15. **Average Credit Score**
    ```
    Name: Average Credit Score
    Expression: AVG(credit_score)
    Product: Corporate Term Loans
    Description: Average credit score across portfolio
    ```

---

## Phase 4: Create Comprehensive Dashboard with All Widget Types

### Step 7: Create Dashboard
Navigate to **Dashboards** → **Create Dashboard**

### Step 8: Configure Dashboard
```
Dashboard Name: "Corporate Term Loans Portfolio Overview"
Description: "Comprehensive analytics dashboard for Corporate Term Loans portfolio"
Product: Corporate Term Loans
Visibility: Public
```

### Step 9: Add All Widget Types (30+ Widgets)

#### KPI Widgets (Key Performance Indicators)
1. **Total Portfolio Value** (KPI)
   - Data Source: Formula
   - Formula: Total Portfolio Value
   - Format: Currency
   - Precision: 2
   - Prefix: "ZMW"

2. **Active Portfolio Value** (KPI)
   - Data Source: Formula
   - Formula: Active Portfolio Value
   - Format: Currency

3. **Loan Count** (KPI)
   - Data Source: Formula
   - Formula: Loan Count
   - Format: Number

4. **Average Loan Size** (KPI)
   - Data Source: Formula
   - Formula: Average Loan Size
   - Format: Currency

5. **NPL Ratio** (KPI)
   - Data Source: Formula
   - Formula: NPL Ratio
   - Format: Percentage
   - Precision: 2
   - Suffix: "%"

#### Chart Widgets (Data Visualization)

6. **Portfolio Distribution by Sector** (Bar Chart)
   - Data Source: Raw Data
   - X-Axis: sector
   - Y-Axis: outstanding_balance
   - Aggregation: SUM
   - Chart Type: Bar

7. **Risk Rating Distribution** (Pie Chart)
   - Data Source: Raw Data
   - X-Axis: risk_rating
   - Y-Axis: COUNT(*)
   - Aggregation: COUNT
   - Chart Type: Pie

8. **Loan Size Distribution** (Histogram)
   - Data Source: Raw Data
   - X-Axis: outstanding_balance (binned)
   - Y-Axis: COUNT(*)
   - Aggregation: COUNT
   - Chart Type: Bar

9. **NPL Trend Over Time** (Line Chart)
   - Data Source: Raw Data
   - X-Axis: disbursement_date
   - Y-Axis: outstanding_balance
   - Filter: status = "npl"
   - Chart Type: Line

10. **Interest Rate vs Loan Size** (Scatter Plot)
    - Data Source: Raw Data
    - X-Axis: outstanding_balance
    - Y-Axis: interest_rate
    - Chart Type: Scatter

#### Advanced Analytics Widgets

11. **IFRS 9 Stage Distribution** (Stacked Bar)
    - Data Source: Raw Data
    - X-Axis: stage
    - Y-Axis: expected_credit_loss
    - Aggregation: SUM
    - Chart Type: Stacked Bar

12. **Collateral Coverage Analysis** (Gauge)
    - Data Source: Formula
    - Formula: Capital Adequacy Ratio
    - Format: Percentage
    - Chart Type: Gauge

13. **Top 10 Borrowers** (Table)
    - Data Source: Raw Data
    - Columns: customer_id, outstanding_balance, risk_rating
    - Sort: outstanding_balance DESC
    - Limit: 10

14. **Sector Performance Matrix** (Heatmap)
    - Data Source: Raw Data
    - X-Axis: sector
    - Y-Axis: risk_rating
    - Value: outstanding_balance
    - Aggregation: SUM
    - Chart Type: Heatmap

#### Geographic & Branch Analysis

15. **Portfolio by Branch** (Bar Chart)
    - Data Source: Raw Data
    - X-Axis: branch_code
    - Y-Axis: outstanding_balance
    - Aggregation: SUM
    - Chart Type: Bar

16. **Branch Performance Ranking** (Table)
    - Data Source: Raw Data
    - Columns: branch_code, outstanding_balance, COUNT(*), AVG(interest_rate)
    - Sort: outstanding_balance DESC

#### Risk Management Widgets

17. **Credit Score Distribution** (Histogram)
    - Data Source: Raw Data
    - X-Axis: credit_score (binned)
    - Y-Axis: COUNT(*)
    - Aggregation: COUNT
    - Chart Type: Bar

18. **Days Past Due Analysis** (Bar Chart)
    - Data Source: Raw Data
    - X-Axis: days_past_due (binned)
    - Y-Axis: COUNT(*)
    - Aggregation: COUNT
    - Chart Type: Bar

19. **Guarantee Type Distribution** (Pie Chart)
    - Data Source: Raw Data
    - X-Axis: guarantee_type
    - Y-Axis: COUNT(*)
    - Aggregation: COUNT
    - Chart Type: Pie

#### Financial Performance Widgets

20. **Interest Income Analysis** (Line Chart)
    - Data Source: Raw Data
    - X-Axis: disbursement_date
    - Y-Axis: interest_earned
    - Aggregation: SUM
    - Chart Type: Line

21. **Penalty Income** (KPI)
    - Data Source: Raw Data
    - Metric: SUM(penalty_amount)
    - Format: Currency

22. **Average Interest Rate** (KPI)
    - Data Source: Raw Data
    - Metric: AVG(interest_rate)
    - Format: Percentage
    - Precision: 2

#### Portfolio Quality Widgets

23. **Loan Tenor Distribution** (Bar Chart)
    - Data Source: Raw Data
    - X-Axis: loan_tenor (binned)
    - Y-Axis: COUNT(*)
    - Aggregation: COUNT
    - Chart Type: Bar

24. **Payment Frequency Analysis** (Pie Chart)
    - Data Source: Raw Data
    - X-Axis: payment_frequency
    - Y-Axis: COUNT(*)
    - Aggregation: COUNT
    - Chart Type: Pie

#### Compliance & Regulatory Widgets

25. **Stage-wise ECL Breakdown** (Stacked Bar)
    - Data Source: Formula
    - Formulas: Stage 1 ECL, Stage 2 ECL, Stage 3 ECL
    - Chart Type: Stacked Bar

26. **Regulatory Ratios** (Table)
    - Data Source: Formula
    - Columns: Ratio Name, Value, Threshold
    - Include: NPL Ratio, Capital Adequacy Ratio, LLP Coverage

#### Trend Analysis Widgets

27. **Portfolio Growth Trend** (Line Chart)
    - Data Source: Raw Data
    - X-Axis: disbursement_date (monthly)
    - Y-Axis: outstanding_balance
    - Aggregation: SUM
    - Chart Type: Line

28. **Risk Migration Analysis** (Sankey)
    - Data Source: Raw Data
    - Show: risk rating changes over time
    - Chart Type: Sankey

#### Comparative Analysis Widgets

29. **Industry Benchmarking** (Bar Chart)
    - Data Source: Raw Data
    - X-Axis: industry
    - Y-Axis: outstanding_balance
    - Aggregation: SUM
    - Chart Type: Bar

30. **Customer Segment Analysis** (Stacked Bar)
    - Data Source: Raw Data
    - X-Axis: customer_segment
    - Y-Axis: outstanding_balance
    - Aggregation: SUM
    - Chart Type: Stacked Bar

### Step 10: Save Dashboard
Click **Save Dashboard** to create the comprehensive dashboard.

---

## Phase 5: Create Sample Customers

### Step 11: Create Customer Records
Navigate to **Customers** → **Create Customer**

Create 20+ sample customers with diverse profiles:

#### Corporate Customers
1. **ABC Manufacturing Ltd**
   - Customer Type: Corporate
   - Industry: Manufacturing
   - Sector: Manufacturing
   - Credit Score: 750

2. **XYZ Services Group**
   - Customer Type: Corporate
   - Industry: Professional Services
   - Sector: Services
   - Credit Score: 720

3. **Tech Solutions Inc**
   - Customer Type: Corporate
   - Industry: Technology
   - Sector: Technology
   - Credit Score: 780

[Continue with 17+ more customers across different sectors]

---

## Phase 6: Data Ingestion & Upload

### Step 12: Prepare Sample Data File
Create a CSV file with 100+ loan records:

```csv
customer_id,loan_account_number,principal_amount,outstanding_balance,interest_rate,risk_rating,credit_score,probability_default,loss_given_default,expected_credit_loss,stage,sector,industry,purpose,collateral_value,guarantee_type,status,disbursement_date,maturity_date,days_past_due,customer_segment,loan_tenor,payment_frequency,interest_earned,penalty_amount,loan_loss_provision,branch_code,officer_id
CUST001,LA001,5000000,4500000,12.5,Medium,750,0.05,0.45,101250,1,Manufacturing,Automotive,Equipment Purchase,6000000,Corporate Guarantee,active,2024-01-15,2027-01-15,0,Large Corporate,36,Monthly,562500,0,101250,BR001,OFF001
CUST002,LA002,3000000,2800000,11.8,Low,780,0.03,0.35,29400,1,Services,IT Services,Working Capital,3500000,Personal Guarantee,active,2024-02-01,2026-02-01,0,Medium Corporate,24,Monthly,330400,0,29400,BR002,OFF002
[Continue with 98+ more records...]
```

### Step 13: Upload Data
1. Navigate to **Data Ingestion**
2. Select Product: **Corporate Term Loans**
3. Import Mode: **Append**
4. Upload the CSV file
5. Monitor import progress
6. Verify data import success

---

## Phase 7: Dashboard Verification & Testing

### Step 14: View Dashboard
1. Navigate to **Dashboards**
2. Click on **Corporate Term Loans Portfolio Overview**
3. Verify all 30+ widgets load correctly
4. Check data accuracy and calculations

### Step 15: Test Widget Functionality
- **KPI Widgets**: Verify currency formatting, percentages
- **Chart Widgets**: Test interactivity, hover effects
- **Table Widgets**: Verify sorting, pagination
- **Gauge Widgets**: Check value ranges and colors
- **Heatmap Widgets**: Verify color coding and data representation

### Step 16: Test Filters and Interactivity
- Apply date range filters
- Filter by sector, risk rating, branch
- Test export functionality
- Verify real-time updates

---

## Phase 8: Advanced Features Testing

### Step 17: Test Formula Engine
1. Create complex formulas with multiple conditions
2. Test nested functions
3. Verify calculation accuracy
4. Test formula validation

### Step 18: Test Data Export
1. Export dashboard as PDF
2. Export data as Excel
3. Test API endpoints
4. Verify data integrity

### Step 19: Test User Permissions
1. Test with different user roles (Admin, Analyst, Viewer)
2. Verify access controls
3. Test audit trail functionality

---

## Phase 9: Performance & Scalability Testing

### Step 20: Load Testing
1. Test with large datasets (1000+ records)
2. Monitor dashboard load times
3. Test concurrent user access
4. Verify system stability

### Step 21: Mobile Responsiveness
1. Test dashboard on mobile devices
2. Verify responsive design
3. Test touch interactions
4. Check chart readability

---

## Phase 10: Demo Preparation

### Step 22: Create Demo Script
1. Prepare talking points for each widget
2. Create business scenarios
3. Prepare sample questions and answers
4. Set up demo environment

### Step 23: Final Verification
1. Test complete user journey
2. Verify all features work end-to-end
3. Check for any remaining issues
4. Prepare backup plans

---

## Demo Presentation Flow

### Opening (2 minutes)
- Platform overview
- Login and navigation
- System capabilities summary

### Product & Formula Creation (5 minutes)
- Show product creation process
- Demonstrate formula engine
- Explain field definitions

### Dashboard Creation (8 minutes)
- Show widget configuration
- Demonstrate different chart types
- Explain analytics capabilities

### Data Management (3 minutes)
- Show data upload process
- Demonstrate customer management
- Explain data validation

### Live Dashboard Demo (10 minutes)
- Walk through all widget types
- Show real-time data updates
- Demonstrate filtering and export
- Explain business insights

### Q&A Session (5 minutes)
- Answer technical questions
- Discuss customization options
- Address business requirements

---

## Success Criteria

✅ **All 30+ widgets display correctly**
✅ **All formulas calculate accurately**
✅ **Data upload works seamlessly**
✅ **Dashboard loads within 3 seconds**
✅ **All user interactions work smoothly**
✅ **Export functionality works**
✅ **Mobile responsiveness verified**
✅ **No console errors or warnings**
✅ **Complete end-to-end workflow functional**

---

## Troubleshooting Guide

### Common Issues & Solutions

1. **Widgets not loading**
   - Check formula syntax
   - Verify data source configuration
   - Check browser console for errors

2. **Data import failures**
   - Verify CSV format
   - Check field mappings
   - Validate data types

3. **Performance issues**
   - Optimize formula expressions
   - Use appropriate aggregations
   - Consider data pagination

4. **Permission errors**
   - Verify user roles
   - Check middleware configuration
   - Review policy settings

---

This comprehensive guide ensures you have a fully functional, production-ready demo of the Portfolio Analytics Platform with all widget types and formula combinations working seamlessly.
