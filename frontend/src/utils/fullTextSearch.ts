const normalize = (value: unknown) => {
  if (value === null || value === undefined) {
    return "";
  }
  return String(value).toLowerCase().replace(/\s+/g, " ").trim();
};

const collectText = (value: unknown): string => {
  if (value === null || value === undefined) {
    return "";
  }
  if (Array.isArray(value)) {
    return value.map(collectText).join(" ");
  }
  if (typeof value === "object") {
    return Object.values(value as Record<string, unknown>)
      .map(collectText)
      .join(" ");
  }
  return String(value);
};

export const fullTextSearch = <T>(items: T[], term: string) => {
  const needle = normalize(term);
  if (!needle) {
    return items;
  }
  const words = needle.split(" ");
  return items.filter((item) => {
    const haystack = normalize(collectText(item));
    return words.every((word) => haystack.includes(word));
  });
};
