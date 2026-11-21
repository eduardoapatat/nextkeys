/**
 * Delay for the n-th item of a group entrance: short, non-uniform steps
 * (40, 30, 25, 20… ms) that stop growing after the first row, so a long list
 * never waits on its stagger.
 */
const steps = [0, 40, 70, 95, 115, 130, 140, 150];

export function staggerDelay(index: number): number {
    return steps[Math.min(index, steps.length - 1)]!;
}
