/**
 * Where the coin should turn to next: four full turns, then the face that
 * matches the result. An even number of half turns shows GO (heads).
 */
export function nextCoinRotation(
    currentDegrees: number,
    heads: boolean,
): number {
    const showingGo = (currentDegrees / 180) % 2 === 0;

    return currentDegrees + 1440 + (showingGo === heads ? 0 : 180);
}
