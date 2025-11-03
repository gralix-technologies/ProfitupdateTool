import { useState, useEffect } from 'react';

export const useCurrency = () => {
    const [currency, setCurrency] = useState({
        code: 'ZMW',
        name: 'Zambian Kwacha',
        symbol: 'K',
        display_name: 'Zambian Kwacha (ZMW) - K',
        decimal_places: 2,
        thousands_separator: ',',
        decimal_separator: '.',
        symbol_position: 'before'
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchCurrency();
    }, []);

    const fetchCurrency = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/currency/base', {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch currency: ${response.status} ${response.statusText}`);
            }

            const result = await response.json();
            if (result.success) {
                setCurrency(result.data);
            } else {
                throw new Error(result.message || 'Failed to load currency configuration');
            }
        } catch (err) {
            console.error('Currency fetch error:', err);
            setError(err.message);
            // Keep default currency on error
        } finally {
            setLoading(false);
        }
    };

    const formatAmount = (amount) => {
        if (amount === null || amount === undefined || isNaN(amount)) {
            return 'N/A';
        }

        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: currency.decimal_places,
            maximumFractionDigits: currency.decimal_places
        }).format(amount);

        if (currency.symbol_position === 'before') {
            return currency.symbol + formatted;
        }

        return formatted + ' ' + currency.symbol;
    };

    const formatAmountWithCode = (amount) => {
        if (amount === null || amount === undefined || isNaN(amount)) {
            return 'N/A';
        }

        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: currency.decimal_places,
            maximumFractionDigits: currency.decimal_places
        }).format(amount);

        return currency.code + ' ' + formatted;
    };

    return {
        currency,
        loading,
        error,
        formatAmount,
        formatAmountWithCode,
        currencyCode: currency.code,
        currencySymbol: currency.symbol,
        currencyName: currency.name,
        refreshCurrency: fetchCurrency
    };
};
