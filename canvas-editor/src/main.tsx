import { createRoot } from "react-dom/client";
import { App } from "./App";
import "./style.css";

const el = document.getElementById("canvas-root");
if (el) {
  createRoot(el).render(<App />);
}
