import React from 'react';
import cn from '../../../utils/classnames';
import { Icon } from '../../index';
import styles from './ExpanderCell.module.css';

const ExpanderCell: React.FC<{
  isExpanded: boolean;
  onClick: () => void;
}> = ({ isExpanded, onClick }) => (
  <button
    onClick={onClick}
    className={cn(styles.button, { [styles.buttonExpanded]: isExpanded })}
  >
    {isExpanded ? (
      <Icon icon="minus" className={styles.icon} />
    ) : (
      <Icon icon="plus" className={styles.icon} />
    )}
  </button>
);

export default ExpanderCell;
