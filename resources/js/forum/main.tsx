import { createRoot } from "react-dom/client";
import App from "../forum-polls/AppMain";
import "../forum-polls/index.css";
import "../forum-polls/globals.css";

createRoot(document.getElementById("root")!).render(<App />);
