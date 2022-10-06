import React from 'react';
import ReactDOM from 'react-dom';
import './App.css';
import { EcosystemProvider } from './components/ecosystem/EcosystemContext';
import { ToastProvider } from './components/toast/toastContext';
import PageGitPackages from './pages/PageGitPackages';
import { SettingsProvider } from './settings';
import { Page, TabNavigation } from './theme';
import { pluginString } from './utils/pluginStrings';
import { Route, RouterProvider } from './utils/router';

const app = document.querySelector('#shgi-app');
const shadowbox = document.querySelector('#shgi-shadowbox');
if (!shadowbox) {
  const elem = document.createElement('div');
  elem.id = 'shgi-shadowbox';
  document.body.appendChild(elem);
}

const App = () => (
  <Page title={pluginString('plugin.name')}>
    <TabNavigation />
    <Route page="git-packages">
      <PageGitPackages />
    </Route>
  </Page>
);

if (app) {
  ReactDOM.render(
    <ToastProvider>
      <SettingsProvider>
        <RouterProvider>
          <EcosystemProvider>
            <App />
          </EcosystemProvider>
        </RouterProvider>
      </SettingsProvider>
    </ToastProvider>,
    app
  );
}
