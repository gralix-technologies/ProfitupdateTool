import React from 'react';

export const Table = React.forwardRef(({ className = '', ...props }, ref) => (
    <div className="table-responsive">
        <table
            ref={ref}
            className={`table ${className}`}
            {...props}
        />
    </div>
));

export const TableHeader = React.forwardRef(({ className = '', ...props }, ref) => (
    <thead ref={ref} className={`${className}`} {...props} />
));

export const TableBody = React.forwardRef(({ className = '', ...props }, ref) => (
    <tbody ref={ref} className={`${className}`} {...props} />
));

export const TableRow = React.forwardRef(({ className = '', ...props }, ref) => (
    <tr ref={ref} className={`${className}`} {...props} />
));

export const TableHead = React.forwardRef(({ className = '', ...props }, ref) => (
    <th ref={ref} className={`${className}`} {...props} />
));

export const TableCell = React.forwardRef(({ className = '', ...props }, ref) => (
    <td ref={ref} className={`${className}`} {...props} />
));

Table.displayName = 'Table';
TableHeader.displayName = 'TableHeader';
TableBody.displayName = 'TableBody';
TableRow.displayName = 'TableRow';
TableHead.displayName = 'TableHead';
TableCell.displayName = 'TableCell';