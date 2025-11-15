# Khmer Calendar Algorithms

This document describes the Khmer calendar calculation algorithms implemented in this package. These algorithms are based on "Pratitin Soryakkatik-Chankatik 1900-1999" by Mr. Roath Kim Soeun.

## Overview

The Khmer calendar is a lunisolar calendar system used in Cambodia. It combines lunar months with solar years, requiring complex calculations to determine:

- Whether a year is a normal year (354 days)
- Whether a year is a leap-day year (355 days) 
- Whether a year is a leap-month year (384 days)

## Core Calculations

### Buddhist Era to Gregorian Year Conversion

The Buddhist Era (BE) year is approximately related to the Gregorian (AD) year as:

```
BE = AD + 544  (for most dates after Khmer New Year)
BE = AD + 543  (for dates before Khmer New Year)
```

Note: The exact conversion depends on the date relative to Khmer New Year (typically in April).

### Aharkun (អាហារគុណ ឬ ហារគុណ)

Aharkun is a fundamental value used in calculating leap months and leap days.

**Formula:**
```
aharkun = floor((BE × 292207 + 499) / 800) + 4
```

**Example:**
For year 2000 AD (2544 BE):
```
aharkun = floor((2544 × 292207 + 499) / 800) + 4
        = floor(743481467 / 800) + 4
        = 929351 + 4
        = 929355
```

### Avoman (អាវមាន)

Avoman determines if a given year is a leap-day year.

**Formula:**
```
avoman = (aharkun × 11 + 25) mod 692
```

**Range:** 0-691

**Leap Day Determination:**

1. **If Khmer Solar Leap Year** (kromathupul ≤ 207):
   - If avoman ≤ 126: Year is a leap-day year
   - Otherwise: Normal year

2. **If Non-Khmer Solar Leap Year**:
   - If avoman < 138: Year is a leap-day year
   - **Special Case:** If avoman = 137 AND next year's avoman = 0:
     - Current year is NOT a leap-day year
     - Next year (with avoman = 0) is a leap-day year
   - Otherwise: Normal year

**Example:**
For year 2009 AD (2553 BE), aharkun = 932510:
```
avoman = (932510 × 11 + 25) mod 692
       = (10257610 + 25) mod 692
       = 10257635 mod 692
       = 119
```

Since 2009 is not a Khmer solar leap year and avoman = 119 < 138, year 2009 is a leap-day year.

### Bodithey (បូតិថី)

Bodithey determines if a given year is a leap-month year.

**Formula:**
```
temp = floor((aharkun × 11 + 25) / 692)
bodithey = (temp + aharkun + 29) mod 30
```

**Range:** 0-29

**Leap Month Determination:**

- If bodithey ≥ 25 OR bodithey ≤ 5: Year is a leap-month year
- Otherwise: Normal year

**Special Cases:**

1. **Consecutive 25/5 Rule:**
   - If bodithey = 25 AND next year's bodithey = 5:
     - Year with bodithey 25 is NOT a leap-month year
     - Only the year with bodithey 5 is a leap-month year

2. **Consecutive 24/6 Rule:**
   - If bodithey = 24 AND next year's bodithey = 6:
     - Year with bodithey 24 IS a leap-month year (enforced)

**Example:**
For year 2012 AD (2556 BE), aharkun = 933605:
```
temp = floor((933605 × 11 + 25) / 692)
     = floor(10269680 / 692)
     = 14841

bodithey = (14841 + 933605 + 29) mod 30
         = 948475 mod 30
         = 24
```

Since bodithey = 24 and next year (2013) has bodithey = 6, year 2012 is a leap-month year.

### Kromathupul (ក្រមធុបុល)

Kromathupul is used to determine if a year is a Khmer solar leap year (366 days).

**Formula:**
```
aharkun_mod = (BE × 292207 + 499) mod 800
kromathupul = 800 - aharkun_mod
```

**Range:** 1-800

**Solar Leap Year:**
- If kromathupul ≤ 207: Year is a Khmer solar leap year (366 days)
- Otherwise: Normal solar year (365 days)

## Leap Year Types

### Bodithey Leap

Bodithey leap can have four types:

- **0 (Normal):** Regular year (354 days)
- **1 (Leap Month):** Year has 13 months (384 days)
- **2 (Leap Day):** Year has an extra day in month Jyeshtha (355 days)
- **3 (Both):** Year has both leap month and leap day (intermediate result)

### Protetin Leap

The actual Khmer calendar cannot have both leap month and leap day in the same year. Protetin leap resolves this:

- **0 (Normal):** Regular year (354 days)
- **1 (Leap Month):** Year has 13 months (384 days)
- **2 (Leap Day):** Year has an extra day in month Jyeshtha (355 days)

**Conversion Rules:**

1. If bodithey leap = 3 (both):
   - Current year becomes leap month (1)
   - Next year gets deferred leap day (2)

2. If bodithey leap = 1 or 2:
   - Protetin leap = bodithey leap

3. If bodithey leap = 0:
   - Check previous year:
     - If previous year was type 3: Current year gets deferred leap day (2)
     - Otherwise: Normal year (0)

**Example:**
Year 2004 (2548 BE):
- Bodithey leap = 3 (both leap month and leap day)
- Protetin leap = 1 (leap month only)
- Year 2005 gets deferred leap day: protetin leap = 2

## Days in Khmer Year

Based on the protetin leap type:

- **Normal year (0):** 354 days
- **Leap-day year (2):** 355 days
- **Leap-month year (1):** 384 days

## Days in Khmer Month

**General Rules:**

- **Odd-numbered months:** 30 days
- **Even-numbered months:** 29 days

**Special Cases:**

1. **Month Jyeshtha (ជេស្ឋ):**
   - Normal year: 29 days (even month)
   - Leap-day year: 30 days

2. **Adhikameas Months (បឋមាសាឍ and ទុតិយាសាឍ):**
   - Both have 30 days each
   - Only present in leap-month years

## Epoch-Based Iteration

The calendar uses an epoch date of **January 1, 1900** (Gregorian), which corresponds to:
- Khmer month: Pous (បុស្ស) - month 2
- Khmer day: 1 (1 Keit)

To convert a Gregorian date to Khmer lunar date:

1. Start from epoch date
2. Calculate days difference between target and epoch
3. Iterate through Khmer years from epoch to target:
   - For each year, get number of days (354/355/384)
   - Adjust date forward or backward
4. Once in target year, iterate through months:
   - Get number of days in each month
   - Subtract until reaching target date

This iteration method ensures accurate conversion for any date within the valid range.

## Calendar Data Table (2000-2020)

| Year (AD) | Year (BE) | Aharkun | Avoman | Bodithey | Bod. Leap | Cal. Leap |
|-----------|-----------|---------|--------|----------|-----------|-----------|
| 2000      | 2544      | 929222  | 627    | 11       | N         | D*        |
| 2001      | 2545      | 929588  | 501    | 23       | N         | N         |
| 2002      | 2546      | 929953  | 364    | 4        | M         | M         |
| 2003      | 2547      | 930318  | 227    | 15       | N         | N         |
| 2004      | 2548      | 930683  | 90     | 26       | MD        | M         |
| 2005      | 2549      | 931049  | 656    | 7        | N         | D*        |
| 2006      | 2550      | 931414  | 519    | 18       | N         | N         |
| 2007      | 2551      | 931779  | 382    | 29       | M         | M         |
| 2008      | 2552      | 932144  | 245    | 10       | N         | N         |
| 2009      | 2553      | 932510  | 119    | 22       | D         | D         |
| 2010      | 2554      | 932875  | 674    | 2        | M         | M         |
| 2011      | 2555      | 933240  | 537    | 13       | N         | N         |
| 2012      | 2556      | 933605  | 400    | 24       | M         | M         |
| 2013      | 2557      | 933971  | 274    | 6        | N         | N         |
| 2014      | 2558      | 934336  | 137    | 17       | N         | N         |
| 2015      | 2559      | 934701  | 0      | 28       | MD        | M         |
| 2016      | 2560      | 935067  | 566    | 9        | N         | D*        |
| 2017      | 2561      | 935432  | 429    | 20       | N         | N         |
| 2018      | 2562      | 935797  | 292    | 1        | M         | M         |
| 2019      | 2563      | 936162  | 155    | 12       | N         | N         |
| 2020      | 2564      | 936528  | 29     | 24       | D         | D         |

**Legend:**
- N = Normal year (354 days)
- D = Leap-day year (355 days)
- M = Leap-month year (384 days)
- MD = Both leap month and day (resolved as M, with D deferred)
- * = Deferred leap day from previous year

**Notes:**
- Year 2000: Gets deferred leap day from 1999 (which was MD)
- Year 2004: Has both (MD), becomes M only; 2005 gets deferred D
- Year 2015: Has both (MD), becomes M only; 2016 gets deferred D
- Year 2014: Avoman = 137 with next year = 0, so 2014 is NOT leap day

## References

1. **Mr. Roath Kim Soeun** - "Pratitin Soryakkatik-Chankatik 1900-1999"
2. Khmer Calendar Algorithm documentation
3. Traditional Khmer astronomical calculations

## Implementation Notes

- All calculations use Buddhist Era (BE) years as input
- The implementation uses private methods in `LunisolarCalculator` class
- Year conversion from AD to BE is approximate (AD + 544) for algorithm calculations
- Exact BE year depends on date relative to Khmer New Year
- Epoch date: January 1, 1900 (AD) = 1 Keit of Pous (Khmer month 2)

