import React from 'react';
import { useEcosystemNotices } from '../../components/ecosystem/EcosystemContext';
import cn from '../../utils/classnames';
import Notice from '../layout/Notice';
import styles from './Page.css';

const Page = ({
  title,
  className = '',
  children,
}: {
  title: string;
  className?: string;
  children?: any;
}) => {
  const notices = useEcosystemNotices();

  return (
    <div className={cn(styles.page, 'wrap', 'metabox-holder', className)}>
      <h1 className={styles.title}>{title}</h1>
      {notices.length !== 0 && (
        <div className={styles.notices}>
          {notices.map((notice) => (
            <Notice
              className={styles.notice}
              type={notice.type}
              message={notice.message}
            />
          ))}
        </div>
      )}
      <div className={styles.content}>{children}</div>
    </div>
  );
};

export default Page;
