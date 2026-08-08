const labels: Record<string, string> = {
  active: 'Active', restricted: 'Restricted', checking: 'Checking', savings: 'Savings', open: 'Open', closed: 'Closed'
};

export const displayLabel = (value: string): string => labels[value] ?? 'Unavailable';
