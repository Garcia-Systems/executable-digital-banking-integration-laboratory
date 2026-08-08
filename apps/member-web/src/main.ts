import './styles.css';
import { HarborApiClient } from './api';
import { renderMemberPage } from './render';
import { MemberPage } from './state';
import { InMemoryAnalyticsRecorder } from './analytics';

const root = document.querySelector<HTMLElement>('#app');
if (!root) throw new Error('Member Web root element is missing.');

const baseUrl = import.meta.env.VITE_HARBOR_API_BASE_URL ?? 'http://127.0.0.1:8080';
const analytics=new InMemoryAnalyticsRecorder();
analytics.record('page_view');
const page = new MemberPage(new HarborApiClient(baseUrl), 'member-0001',undefined,undefined,analytics);
page.subscribe(state => renderMemberPage(root, state, page));
void page.load();
