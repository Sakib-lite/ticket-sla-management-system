import { classNames } from "@/utils";

type LoaderProps = {
  size?: number;
  className?: string;
};

export default function Loader({ size = 16, className }: LoaderProps) {
  return (
    <span
      style={{ width: size, height: size }}
      className={classNames(
        "inline-block animate-spin rounded-full border-2 border-current border-t-transparent",
        className
      )}
    />
  );
}
