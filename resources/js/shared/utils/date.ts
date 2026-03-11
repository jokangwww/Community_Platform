/**
 * Centralized date formatting utility.
 *
 * Change the implementation here to update every date display across
 * the entire application (forum, polls, petitions, buddy programme, etc.).
 *
 * Current format: DD-MMM-YYYY  (e.g. 12-Mar-2026)
 */

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/**
 * Format a Date object to the standard application date string.
 */
export function formatDate(input: Date | string): string {
  const date = typeof input === 'string' ? new Date(input) : input;
  const day = date.getDate().toString().padStart(2, '0');
  const month = MONTH_NAMES[date.getMonth()];
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
}
